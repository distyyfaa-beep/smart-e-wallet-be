<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Get current wallet balance.
     * GET /api/wallet
     */
    public function show(Request $request)
    {
        $user   = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json(['message' => 'Wallet tidak ditemukan.'], 404);
        }

        return response()->json([
            'username' => $user->username,
            'email'    => $user->email,
            'balance'  => (float) $wallet->balance,
        ]);
    }

    /**
     * Top up wallet balance.
     * POST /api/topup
     */
    public function topup(Request $request)
    {
        $request->validate([
            'amount' => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],
        ]);

        $user   = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json(['message' => 'Wallet tidak ditemukan.'], 404);
        }

        DB::transaction(function () use ($wallet, $user, $request) {
            $wallet->increment('balance', $request->amount);

            Transaction::create([
                'sender_id'   => null,
                'receiver_id' => $user->id,
                'amount'      => $request->amount,
                'type'        => 'topup',
                'description' => 'Top up saldo',
            ]);
        });

        $wallet->refresh();

        return response()->json([
            'message'     => 'Top up berhasil.',
            'amount'      => (int) $request->amount,
            'balance'     => (float) $wallet->balance,
        ]);
    }

    /**
     * Transfer balance to another user.
     * POST /api/transfer
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'recipient'   => 'required|string',
            'amount'      => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],
        ]);

        $sender = $request->user();

        // Find recipient by email or phone
        $recipient = \App\Models\User::where('email', $request->recipient)
            ->orWhere('phone', $request->recipient)
            ->first();

        if (!$recipient) {
            return response()->json([
                'message' => 'Pengguna penerima tidak ditemukan.',
            ], 404);
        }

        if ($recipient->id === $sender->id) {
            return response()->json([
                'message' => 'Tidak bisa transfer ke diri sendiri.',
            ], 400);
        }

        $senderWallet    = $sender->wallet;
        $recipientWallet = $recipient->wallet;

        if (!$senderWallet || !$recipientWallet) {
            return response()->json(['message' => 'Wallet tidak ditemukan.'], 404);
        }

        if ($senderWallet->balance < $request->amount) {
            return response()->json([
                'message' => 'Saldo tidak cukup.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($senderWallet, $recipientWallet, $sender, $recipient, $request) {
                // Deduct from sender
                $senderWallet->decrement('balance', $request->amount);

                // Add to recipient
                $recipientWallet->increment('balance', $request->amount);

                // Record transfer_out for sender
                Transaction::create([
                    'sender_id'   => $sender->id,
                    'receiver_id' => $recipient->id,
                    'amount'      => $request->amount,
                    'type'        => 'transfer_out',
                    'description' => 'Transfer ke ' . $recipient->username,
                ]);

                // Record transfer_in for recipient
                Transaction::create([
                    'sender_id'   => $sender->id,
                    'receiver_id' => $recipient->id,
                    'amount'      => $request->amount,
                    'type'        => 'transfer_in',
                    'description' => 'Transfer dari ' . $sender->username,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transfer gagal. Silakan coba lagi.',
            ], 500);
        }

        $senderWallet->refresh();

        return response()->json([
            'message'   => 'Transfer berhasil.',
            'recipient' => $recipient->username,
            'amount'    => (int) $request->amount,
            'balance'   => (float) $senderWallet->balance,
        ]);
    }

    /**
     * Get transaction history for authenticated user.
     * GET /api/transactions
     */
    public function transactions(Request $request)
    {
        $user = $request->user();

        $transactions = Transaction::where(function ($query) use ($user) {
                // transfer_out → sender is user
                $query->where('sender_id', $user->id)->where('type', 'transfer_out');
            })
            ->orWhere(function ($query) use ($user) {
                // transfer_in and topup → receiver is user
                $query->where('receiver_id', $user->id)
                      ->whereIn('type', ['transfer_in', 'topup']);
            })
            ->with(['sender:id,username', 'receiver:id,username'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trx) use ($user) {
                return [
                    'id'          => $trx->id,
                    'type'        => $trx->type,
                    'amount'      => (float) $trx->amount,
                    'description' => $trx->description,
                    'from'        => $trx->sender?->username ?? 'System',
                    'to'          => $trx->receiver?->username ?? '-',
                    'created_at'  => $trx->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'transactions' => $transactions,
        ]);
    }
}
