<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EditRequest;
use App\Support\TransactionEditor;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $requests = EditRequest::with('transaction', 'pengaju', 'penyetuju')
            ->where('branch_id', current_branch())
            ->when($request->get('status', 'pending'), fn ($q, $v) => $q->where('status', $v))
            ->latest('id')->paginate(20)->withQueryString();

        return view('approvals.index', [
            'requests' => $requests,
            'status' => $request->get('status', 'pending'),
            'jmlPending' => EditRequest::pending()->where('branch_id', current_branch())->count(),
        ]);
    }

    public function approve(EditRequest $editRequest)
    {
        if ($editRequest->status !== 'pending') {
            return back()->with('warning', 'Pengajuan sudah diproses.');
        }
        $trx = $editRequest->transaction;
        if (! $trx || ($editRequest->jenis === 'batal' && $trx->status === 'batal')) {
            return back()->with('error', 'Transaksi tidak dapat diproses.');
        }

        try {
            if ($editRequest->jenis === 'edit') {
                $ket = TransactionEditor::apply($trx, $editRequest->payload);
                $detail = $ket ? implode(', ', $ket) : 'ubah data';
            } else {
                TransactionEditor::cancel($trx, $editRequest->alasan);
                $detail = 'pembatalan';
            }
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Gagal menerapkan: ' . $e->getMessage());
        }

        $editRequest->update(['status' => 'approved', 'approved_by' => auth()->id(), 'decided_at' => now()]);
        ActivityLog::catat('setujui_' . $editRequest->jenis, mb_strimwidth("{$trx->no_nota}: {$detail}", 0, 250, '…'), 'transaction', $trx->id);

        return back()->with('success', 'Pengajuan disetujui & diterapkan.');
    }

    public function reject(Request $request, EditRequest $editRequest)
    {
        if ($editRequest->status !== 'pending') {
            return back()->with('warning', 'Pengajuan sudah diproses.');
        }
        $data = $request->validate(['catatan' => 'required|string|max:255'], [], ['catatan' => 'alasan penolakan']);

        $editRequest->update(['status' => 'ditolak', 'approved_by' => auth()->id(), 'catatan' => $data['catatan'], 'decided_at' => now()]);
        ActivityLog::catat('tolak_' . $editRequest->jenis, "{$editRequest->transaction?->no_nota} — {$data['catatan']}", 'transaction', $editRequest->transaction_id);

        return back()->with('success', 'Pengajuan ditolak.');
    }
}
