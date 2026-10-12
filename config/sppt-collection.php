<?php

return [
    /*
    | Klasifikasi akaun (NPL Tracking) — Aging Analysis
    | Lancar: tiada tunggakan / 0 hari lewat
    | Perhatian Khusus: 1–90 hari tunggakan
    | Tidak Lancar (NPL): >90 hari tunggakan
    */
    'npl' => [
        'perhatian_khusus_min_hari' => 1,
        'tidak_lancar_min_hari' => 91,
        'labels' => [
            'lancar' => 'Lancar',
            'perhatian_khusus' => 'Perhatian Khusus',
            'tidak_lancar' => 'Tidak Lancar (NPL)',
        ],
    ],

    /*
    | Garis masa tindakan kutipan automatik (Automated Dunning)
    | Sistem menjana & menghantar mengikut tahap hari tunggakan.
    */
    'dunning' => [
        [
            'tahap' => 'sms_amaran',
            'jenis' => 'sms',
            'label' => 'SMS Amaran',
            'min_hari' => 7,
            'max_hari' => 29,
            'sms_template' => 'TEKUN: Akaun {no_akaun} tertunggak RM{tunggakan}. Sila bayar segera. Hubungi cawangan {cawangan}.',
        ],
        [
            'tahap' => 'notis_1',
            'jenis' => 'surat',
            'label' => 'Notis Peringatan 1',
            'min_hari' => 30,
            'max_hari' => 59,
        ],
        [
            'tahap' => 'notis_2',
            'jenis' => 'surat',
            'label' => 'Notis Peringatan 2',
            'min_hari' => 60,
            'max_hari' => 89,
        ],
        [
            'tahap' => 'notis_3',
            'jenis' => 'surat',
            'label' => 'Notis Peringatan 3 (Surat Tuntutan Rasmi)',
            'min_hari' => 90,
            'max_hari' => null,
        ],
    ],
];
