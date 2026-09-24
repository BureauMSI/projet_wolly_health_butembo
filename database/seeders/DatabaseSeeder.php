<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\EquilibriumRule;
use App\Models\ExchangeRate;
use App\Models\Institution;
use App\Models\PlanSetting;
use App\Models\Product;
use App\Models\RewardTier;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Currency::query()->updateOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'is_default' => true],
        );

        Currency::query()->updateOrCreate(
            ['code' => 'CDF'],
            ['name' => 'Franc congolais', 'is_default' => false],
        );

        $institution = Institution::query()->firstOrCreate(
            ['name' => 'Holy Health'],
            [
                'acronym' => 'HH',
                'city' => 'Butembo',
                'country' => 'RDC',
                'default_locale' => 'fr',
                'default_currency_code' => 'USD',
            ],
        );

        $branch = Branch::query()->firstOrCreate(
            ['code' => 'BTB'],
            [
                'institution_id' => $institution->id,
                'name' => 'Butembo',
                'phone' => '099000000',
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrateur',
                'password' => 'Admin1234',
                'role' => 'admin',
                'locale' => 'fr',
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['username' => 'caisse'],
            [
                'name' => 'Caisse Butembo',
                'password' => 'Caisse1234',
                'role' => 'cashier',
                'branch_id' => $branch->id,
                'locale' => 'fr',
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['username' => 'comptable'],
            [
                'name' => 'Comptable Butembo',
                'password' => 'Compta1234',
                'role' => 'accountant',
                'branch_id' => $branch->id,
                'locale' => 'fr',
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['username' => 'responsable'],
            [
                'name' => 'Responsable Butembo',
                'password' => 'Resp1234',
                'role' => 'manager',
                'branch_id' => $branch->id,
                'locale' => 'fr',
                'is_active' => true,
            ],
        );

        ExchangeRate::query()->firstOrCreate(
            ['currency_code' => 'USD', 'effective_at' => '2020-01-01 00:00:00'],
            ['rate_to_usd' => 1],
        );

        ExchangeRate::query()->firstOrCreate(
            ['currency_code' => 'CDF', 'effective_at' => '2020-01-01 00:00:00'],
            ['rate_to_usd' => 0.000357],
        );

        $threshold = '40';
        $settings = [
            'sponsorship_amount_usd' => '15',
            'membership_amount_usd' => '0',
            'membership_pv_threshold' => $threshold,
            'sponsorship_pv' => $threshold,
            'username_suffix_length' => '4',
            'default_member_password' => 'ChangeMe123',
            'member_discount_percent' => '0',
        ];

        foreach ($settings as $key => $value) {
            PlanSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Product::query()->firstOrCreate(
            ['code' => 'HH-VIT'],
            [
                'name' => 'Vitamine C',
                'unit_price_usd' => 2,
                'member_unit_price_usd' => 2,
                'pv_per_tablet' => 1,
                'box_price_usd' => 18,
                'member_box_price_usd' => 18,
                'box_pv' => 12,
                'is_active' => true,
            ],
        );

        Product::query()->firstOrCreate(
            ['code' => 'HH-OMG'],
            [
                'name' => 'Omega 3',
                'unit_price_usd' => 4,
                'member_unit_price_usd' => 4,
                'pv_per_tablet' => 2,
                'box_price_usd' => 36,
                'member_box_price_usd' => 36,
                'box_pv' => 20,
                'is_active' => true,
            ],
        );

        RewardTier::query()->whereIn('label', ['Bronze', 'Argent'])->update(['is_active' => false]);

        foreach ([
            ['label' => 'Machine à coudre', 'min_pv' => 160, 'sort_order' => 1, 'image_path' => 'images/rewards/sewing-machine.png'],
            ['label' => 'Téléphone', 'min_pv' => 1000, 'sort_order' => 2, 'image_path' => 'images/rewards/phone.png'],
            ['label' => 'Moto', 'min_pv' => 10000, 'sort_order' => 3, 'image_path' => 'images/rewards/moto.png'],
            ['label' => 'Voiture', 'min_pv' => 24000, 'sort_order' => 4, 'image_path' => 'images/rewards/car.png'],
        ] as $prize) {
            RewardTier::query()->updateOrCreate(
                ['label' => $prize['label']],
                [
                    'min_pv' => $prize['min_pv'],
                    'amount_usd' => 0,
                    'image_path' => $prize['image_path'],
                    'sort_order' => $prize['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        foreach ([
            ['scope' => 'generations_1_4', 'generation' => 1, 'amount_usd' => 4],
            ['scope' => 'generations_1_4', 'generation' => 2, 'amount_usd' => 4],
            ['scope' => 'generations_1_4', 'generation' => 3, 'amount_usd' => 4],
            ['scope' => 'generations_1_4', 'generation' => 4, 'amount_usd' => 4],
            ['scope' => 'after_generation_4', 'generation' => null, 'amount_usd' => 1],
        ] as $rule) {
            $row = EquilibriumRule::withTrashed()
                ->where('scope', $rule['scope'])
                ->where(function ($q) use ($rule) {
                    if ($rule['generation'] === null) {
                        $q->whereNull('generation');
                    } else {
                        $q->where('generation', $rule['generation']);
                    }
                })
                ->first();

            if ($row === null) {
                EquilibriumRule::query()->create([
                    'scope' => $rule['scope'],
                    'generation' => $rule['generation'],
                    'amount_usd' => $rule['amount_usd'],
                    'is_active' => true,
                ]);
            } else {
                if ($row->trashed()) {
                    $row->restore();
                }
                $row->update([
                    'amount_usd' => $rule['amount_usd'],
                    'is_active' => true,
                ]);
            }
        }

        $templates = [
            ['welcome', 'fr', 'Bienvenue {{name}}. Identifiant: {{username}} Mot de passe: {{password}}'],
            ['welcome', 'sw', 'Karibu {{name}}. Jina la mtumiaji: {{username}} Nenosiri: {{password}}'],
            ['receipt', 'fr', 'Reçu {{number}} total {{total}} {{currency}}'],
            ['receipt', 'sw', 'Risiti {{number}} jumla {{total}} {{currency}}'],
            ['membership-threshold', 'fr', 'Le seuil d adhésion est atteint pour {{name}}. PV: {{pv}}'],
            ['membership-threshold', 'sw', 'Kiwango cha uanachama kimefikiwa kwa {{name}}. PV: {{pv}}'],
            ['bonus', 'fr', 'Bonus {{type}} de {{amount}} USD crédité.'],
            ['bonus', 'sw', 'Bonasi {{type}} ya {{amount}} USD imeongezwa.'],
        ];

        foreach ($templates as [$key, $locale, $body]) {
            WhatsappTemplate::query()->updateOrCreate(
                ['key' => $key, 'locale' => $locale],
                ['body' => $body],
            );
        }
    }
}
