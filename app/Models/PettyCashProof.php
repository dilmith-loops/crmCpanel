<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCashProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'petty_cash_request_id',
        'file_path',
        'file_name',
        'file_type',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        try {
            return route('petty-cash.proofs.show', $this->id);
        } catch (\Throwable $e) {
            return url($this->file_path);
        }
    }

    public function request()
    {
        return $this->belongsTo(PettyCashRequest::class, 'petty_cash_request_id');
    }

    /**
     * Store an uploaded proof file and create its model instance.
     */
    public static function createFromFile(\Illuminate\Http\UploadedFile $file, int $pettyCashId): self
    {
        $origName = $file->getClientOriginalName();
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
        $filename = time() . '_' . uniqid() . '_' . $safeName;

        $destinationPath = public_path('uploads/petty_cash_proofs');
        if (!file_exists($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }

        try {
            $file->move($destinationPath, $filename);
        } catch (\Throwable $fe) {
            \Illuminate\Support\Facades\Log::error('Proof file move failed: ' . $fe->getMessage());
            throw new \RuntimeException('Failed to save uploaded proof file: ' . $origName);
        }

        $filePath = 'uploads/petty_cash_proofs/' . $filename;

        // Mirror to alternative paths for shared hosting / LiteSpeed deployments
        $alt1 = base_path('public/uploads/petty_cash_proofs');
        if ($destinationPath !== $alt1 && !file_exists($alt1 . '/' . $filename)) {
            @mkdir($alt1, 0755, true);
            @copy($destinationPath . '/' . $filename, $alt1 . '/' . $filename);
        }
        $alt2 = base_path('uploads/petty_cash_proofs');
        if ($destinationPath !== $alt2 && !file_exists($alt2 . '/' . $filename)) {
            @mkdir($alt2, 0755, true);
            @copy($destinationPath . '/' . $filename, $alt2 . '/' . $filename);
        }

        return self::create([
            'petty_cash_request_id' => $pettyCashId,
            'file_path' => $filePath,
            'file_name' => $origName,
            'file_type' => $file->getClientMimeType(),
        ]);
    }
}
