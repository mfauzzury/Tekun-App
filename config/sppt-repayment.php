<?php

return [
    /*
    | Parameter Ta'widh (ganti rugi) — rujukan LPS TEKUN (POC).
    | Kadar harian atas jumlah tunggakan selepas tempoh tangguh.
    */
    'tawidh' => [
        'hari_tangguh' => 7,
        'kadar_harian_pct' => 0.0333,
        'max_pct_tahunan' => 12.0,
    ],

    'saluran_bayaran' => [
        'fpx' => 'FPX / TEKUNPay',
        'jompay' => 'JomPAY',
        'kaunter' => 'Kaunter Fizikal',
        'auto_debit' => 'Auto Debit (e-Mandate)',
        'ewallet' => 'e-Wallet',
    ],

    /** Scenario 2 — flat rate vs reducing balance formula defaults (POC). */
    'formula' => [
        'default_kadar_keuntungan_tahunan' => (float) env('SPPT_DEFAULT_PROFIT_RATE', 4),
        'default_tempoh_bulan' => (int) env('SPPT_DEFAULT_TEMPOH_BULAN', 36),
        'tempoh_bulan_options' => [12, 24, 36, 48, 60, 84, 120],
    ],
];
