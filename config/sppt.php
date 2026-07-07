<?php

return [
    /** Max permohonan supporting-document size per file (kilobytes). 10240 = 10MB. */
    'permohonan_document_max_kb' => (int) env('SPPT_DOCUMENT_MAX_KB', 10240),

    /** Spec 1.7.5 — AI document classifier categories for permohonan supporting documents. */
    'document_classes' => [
        'ic_pemohon_depan' => 'IC Pemohon (Depan)',
        'ic_pemohon_belakang' => 'IC Pemohon (Belakang)',
        'ic_pemohon_combined' => 'IC Pemohon (Depan & Belakang)',
        'ic_pasangan_depan' => 'IC Pasangan (Depan)',
        'ic_pasangan_belakang' => 'IC Pasangan (Belakang)',
        'ic_pasangan_combined' => 'IC Pasangan (Depan & Belakang)',
        'ssm_form_9' => 'SSM Form 9',
        'lesen_pbt' => 'Lesen / Permit daripada PBT',
        'penyata_bank' => 'Penyata Bank',
        'plan_perniagaan' => 'Plan Perniagaan',
        'lain_lain' => 'Lain-Lain',
    ],

    /** Setup dataset keys consumed by AI services (see config/sppt-setup.php). */
    'ai_setup_keys' => [
        'tekun_niaga_eligibility' => 'kelayakan_tekun_niaga',
    ],

    /** Spec 2.1.5 — configurable credit scoring thresholds & decision engine bands. */
    'credit_scoring' => [
        'auto_approve_min_score' => (int) env('SPPT_CREDIT_AUTO_APPROVE_MIN', 80),
        'officer_review_min_score' => (int) env('SPPT_CREDIT_OFFICER_REVIEW_MIN', 60),
    ],

    /** Surat Tawaran Kemudahan Pembiayaan — branch letterhead & default financing terms. */
    'offer_letter' => [
        'branch_address' => env('SPPT_BRANCH_ADDRESS', 'Aras 1, Menara TEKUN, Jalan Raja Laut, 50350 Kuala Lumpur'),
        'branch_phone' => env('SPPT_BRANCH_PHONE', '03-2698 5000'),
        'branch_email' => env('SPPT_BRANCH_EMAIL', 'info@tekun.gov.my'),
        'default_profit_rate' => (float) env('SPPT_DEFAULT_PROFIT_RATE', 4),
        'default_syariah_concept' => env('SPPT_DEFAULT_SYARIAH_CONCEPT', 'Tawarruq'),
        'signatory_name' => env('SPPT_OFFER_SIGNATORY_NAME', ''),
        'signatory_title' => env('SPPT_OFFER_SIGNATORY_TITLE', 'Pegawai Pembiayaan'),
        'logo_path' => env('SPPT_LOGO_PATH', base_path('logo_tekun.png')),
        'acceptance_days' => (int) env('SPPT_OFFER_ACCEPTANCE_DAYS', 14),
    ],
];
