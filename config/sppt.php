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
];
