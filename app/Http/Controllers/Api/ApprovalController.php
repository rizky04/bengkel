<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EditRequest;
use App\Support\TransactionEditor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        return [
            'data' => EditRequest::with('transaction:id,no_nota', 'pengaju:id,name', 'penyetuju:id,name')
                ->where('branch_id', current_branch())
                ->where('status', $status)
                ->latest('id')->limit(100)->get(),
            'jmlPending' => EditRequest::pending()->where('branch_id', current_branch())->count(),
        ];
    }

    public function approve(EditRequest $editRequest)
    {
        if ($editRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Pengajuan sudah diproses.']);
        }
        $trx = $editRequest->transaction;
        if (! $trx || ($editRequest->jenis === 'batal' && $trx->status === 'batal')) {
            throw ValidationException::withMessages(['status' => 'Transaksi tidak dapat diproses.']);
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
            throw ValidationException::withMessages(['status' => 'Gagal menerapkan: ' . $e->getMessage()]);
        }

        $editRequest->update(['status' => 'approved', 'approved_by' => auth()->id(), 'decided_at' => now()]);
        ActivityLog::catat('setujui_' . $editRequest->jenis, mb_strimwidth("{$trx->no_nota}: {$detail}", 0, 250, '…'), 'transaction', $trx->id);

        return ['ok' => true];
    }

    public function reject(Request $request, EditRequest $editRequest)
    {
        if ($editRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Pengajuan sudah diproses.']);
        }
        $data = $request->validate(['catatan' => 'required|string|max:255']);

        $editRequest->update(['status' => 'ditolak', 'approved_by' => auth()->id(), 'catatan' => $data['catatan'], 'decided_at' => now()]);
        ActivityLog::catat('tolak_' . $editRequest->jenis, "{$editRequest->transaction?->no_nota} — {$data['catatan']}", 'transaction', $editRequest->transaction_id);

        return ['ok' => true];
    }
}
