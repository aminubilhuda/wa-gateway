<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $guarded = [];

    /**
     * Resolve target recipient phone numbers for this campaign.
     */
    public function getRecipientNumbers(): array
    {
        if ($this->target_type === 'all') {
            return Contact::where('is_active', true)->pluck('phone_number')->toArray();
        }

        if ($this->target_type === 'group') {
            return Contact::where('is_active', true)
                ->where('label', $this->target_value)
                ->pluck('phone_number')
                ->toArray();
        }

        if ($this->target_type === 'manual') {
            return array_filter(array_map('trim', explode(',', $this->target_value)));
        }

        if ($this->target_type === 'random') {
            // target_value can be a count of random contacts
            $count = (int) $this->target_value ?: 5;

            return Contact::where('is_active', true)
                ->inRandomOrder()
                ->limit($count)
                ->pluck('phone_number')
                ->toArray();
        }

        if ($this->target_type === 'excel') {
            if (str_starts_with($this->target_value, '[')) {
                $data = json_decode($this->target_value, true);
                if (is_array($data)) {
                    $numbers = [];
                    foreach ($data as $row) {
                        if (isset($row['A'])) {
                            $numbers[] = $row['A'];
                        }
                    }

                    return $numbers;
                }
            }

            return array_filter(array_map('trim', explode(',', $this->target_value)));
        }

        return [];
    }
}
