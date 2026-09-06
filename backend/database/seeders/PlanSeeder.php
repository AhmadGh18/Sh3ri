<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Entitlements\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent — updateOrCreate on `code` so re-seeding preserves ids.
        foreach ($this->plans() as $p) {
            Plan::updateOrCreate(['code' => $p['code']], $p);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function plans(): array
    {
        return [
            [
                'code' => 'guest',
                'name_ar' => 'زائر',
                'name_en' => 'Guest',
                'tagline_ar' => 'استمع لبضع أبيات قبل التسجيل',
                'price_cents' => 0, 'currency' => 'USD',
                'daily_audio_plays' => 30,   // ~1.5 poems/day — enough to sample
                'allow_download' => false,
                'is_public' => false,   // hidden on /plans page
                'sort' => 0,
            ],
            [
                'code' => 'free',
                'name_ar' => 'مجاني',
                'name_en' => 'Free',
                'tagline_ar' => 'كل الميزات، حصّة استماع يومية',
                'price_cents' => 0, 'currency' => 'USD',
                'daily_audio_plays' => 100,  // signed-in gets a real listening budget
                'allow_download' => false,
                'is_public' => true,
                'sort' => 1,
            ],
            [
                'code' => 'starter',
                'name_ar' => 'بيت الشعر',
                'name_en' => 'Starter',
                'tagline_ar' => 'استمع بلا انقطاع طوال اليوم تقريبًا',
                'price_cents' => 299, 'currency' => 'USD',
                'daily_audio_plays' => 150,
                'allow_download' => false,
                'is_public' => true,
                'sort' => 2,
            ],
            [
                'code' => 'pro',
                'name_ar' => 'المكتبة الكاملة',
                'name_en' => 'Pro',
                'tagline_ar' => 'استماع غير محدود، تحميل الأبيات، جودة أعلى',
                'price_cents' => 699, 'currency' => 'USD',
                'daily_audio_plays' => null,   // unlimited
                'allow_download' => true,
                'is_public' => true,
                'sort' => 3,
            ],
        ];
    }
}
