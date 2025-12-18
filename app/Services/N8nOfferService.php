<?php

namespace App\Services;

use App\Models\Offer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class N8nOfferService
{
    /**
     * Process webhook data from n8n and update offer with file information
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function processOfferPacketWebhook(array $data): array
    {
        // Validate required fields
        $validator = Validator::make($data, [
            'offer_id' => ['required', 'integer', 'exists:offers,id'],
            'file_path' => ['required', 'string'],
            'file_type' => ['required', 'string', 'in:pdf,doc,docx'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Find the offer
        $offer = Offer::find($data['offer_id']);
        
        if (!$offer) {
            throw new ModelNotFoundException('Offer not found');
        }

        // Update offer with file information
        $offer->file_path = $data['file_path'];
        $offer->file_type = $data['file_type'];
        $offer->save();

        Log::info('Offer packet webhook processed successfully', [
            'offer_id' => $offer->id,
            'file_path' => $data['file_path'],
            'file_type' => $data['file_type'],
        ]);

        return [
            'success' => true,
            'message' => 'Offer packet file information saved successfully',
            'offer_id' => $offer->id,
            'file_path' => $data['file_path'],
            'file_type' => $data['file_type'],
        ];
    }
}

