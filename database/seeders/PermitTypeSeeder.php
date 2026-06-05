<?php

namespace Database\Seeders;

use App\Enums\DurationType;
use App\Models\PermitType;
use Illuminate\Database\Seeder;

class PermitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $permitTypes = [
            ['name_en' => 'Cold Work', 'name_ar' => 'عمل بارد', 'duration_type' => DurationType::Weekly],
            ['name_en' => 'Hot Work', 'name_ar' => 'عمل حار', 'duration_type' => DurationType::Daily],
            ['name_en' => 'Electrical Work', 'name_ar' => 'عمل كهربائي', 'duration_type' => DurationType::Weekly],
            ['name_en' => 'Work at Height', 'name_ar' => 'عمل في ارتفاع', 'duration_type' => DurationType::Weekly],
            ['name_en' => 'Lifting Work', 'name_ar' => 'عمل رفع', 'duration_type' => DurationType::Weekly],
            ['name_en' => 'Confined Space', 'name_ar' => 'مكان مغلق', 'duration_type' => DurationType::Daily],
            ['name_en' => 'Excavation Work', 'name_ar' => 'عمل حفر', 'duration_type' => DurationType::Daily],
            ['name_en' => 'Chemical Work', 'name_ar' => 'عمل كيميائي', 'duration_type' => DurationType::Daily],
            ['name_en' => 'Testing/Inspection', 'name_ar' => 'اختبار/فحص', 'duration_type' => DurationType::Weekly],
            ['name_en' => 'Emergency Work', 'name_ar' => 'عمل طارئ', 'duration_type' => DurationType::Daily],
        ];

        foreach ($permitTypes as $type) {
            PermitType::create([
                'name_en' => $type['name_en'],
                'name_ar' => $type['name_ar'],
                'duration_type' => $type['duration_type'],
                'is_active' => true,
            ]);
        }
    }
}
