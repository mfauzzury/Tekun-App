<?php

return [
    'categories' => [
        'status_permohonan' => [
            'label' => 'Status Permohonan',
            'labelEn' => 'Application Status',
            'description' => 'Status aliran kerja permohonan pembiayaan',
        ],
        'status_penilaian' => [
            'label' => 'Status Penilaian',
            'labelEn' => 'Assessment Status',
            'description' => 'Status penilaian dan kelulusan pembiayaan',
        ],
        'status_akaun' => [
            'label' => 'Status Akaun',
            'labelEn' => 'Account Status',
            'description' => 'Status akaun pembiayaan aktif',
        ],
        'status_pengeluaran_dana' => [
            'label' => 'Status Pengeluaran Dana',
            'labelEn' => 'Fund Disbursement Status',
            'description' => 'Status pengeluaran dan batch dana',
        ],
        'status_jaminan' => [
            'label' => 'Status Jaminan',
            'labelEn' => 'Collateral Status',
            'description' => 'Status jaminan dan cagaran',
        ],
        'status_kutipan' => [
            'label' => 'Status Kutipan',
            'labelEn' => 'Collection Status',
            'description' => 'Status lawatan dan kutipan lapangan',
        ],
        'status_usahawan' => [
            'label' => 'Status Usahawan',
            'labelEn' => 'Entrepreneur Status',
            'description' => 'Status rekod usahawan',
        ],
        'status_bayaran' => [
            'label' => 'Status Bayaran',
            'labelEn' => 'Payment Status',
            'description' => 'Status transaksi bayaran pembiayaan',
        ],
    ],

    'defaults' => [
        'status_permohonan' => [
            ['value' => 'dalam-proses', 'label' => 'Dalam Proses', 'color' => 'amber', 'active' => true, 'sort' => 1],
            ['value' => 'menunggu-dokumen', 'label' => 'Menunggu Dokumen', 'color' => 'slate', 'active' => true, 'sort' => 2],
            ['value' => 'lengkap', 'label' => 'Lengkap', 'color' => 'emerald', 'active' => true, 'sort' => 3],
            ['value' => 'ditolak', 'label' => 'Ditolak', 'color' => 'rose', 'active' => true, 'sort' => 4],
            ['value' => 'dibatalkan', 'label' => 'Dibatalkan', 'color' => 'slate', 'active' => true, 'sort' => 5],
        ],
        'status_penilaian' => [
            ['value' => 'menunggu-penilaian', 'label' => 'Menunggu Penilaian', 'color' => 'amber', 'active' => true, 'sort' => 1],
            ['value' => 'dalam-penilaian', 'label' => 'Dalam Penilaian', 'color' => 'blue', 'active' => true, 'sort' => 2],
            ['value' => 'diluluskan', 'label' => 'Diluluskan', 'color' => 'emerald', 'active' => true, 'sort' => 3],
            ['value' => 'ditolak', 'label' => 'Ditolak', 'color' => 'rose', 'active' => true, 'sort' => 4],
        ],
        'status_akaun' => [
            ['value' => 'aktif', 'label' => 'Aktif', 'color' => 'emerald', 'active' => true, 'sort' => 1],
            ['value' => 'tunggakan', 'label' => 'Tunggakan', 'color' => 'amber', 'active' => true, 'sort' => 2],
            ['value' => 'npf', 'label' => 'NPF', 'color' => 'rose', 'active' => true, 'sort' => 3],
            ['value' => 'selesai-bayar', 'label' => 'Selesai Bayar', 'color' => 'blue', 'active' => true, 'sort' => 4],
            ['value' => 'ditutup', 'label' => 'Ditutup', 'color' => 'slate', 'active' => true, 'sort' => 5],
        ],
        'status_pengeluaran_dana' => [
            ['value' => 'draf', 'label' => 'Draf', 'color' => 'slate', 'active' => true, 'sort' => 1],
            ['value' => 'menunggu', 'label' => 'Menunggu', 'color' => 'amber', 'active' => true, 'sort' => 2],
            ['value' => 'menunggu-kelulusan', 'label' => 'Menunggu Kelulusan', 'color' => 'amber', 'active' => true, 'sort' => 3],
            ['value' => 'berjaya', 'label' => 'Berjaya', 'color' => 'emerald', 'active' => true, 'sort' => 4],
            ['value' => 'gagal', 'label' => 'Gagal', 'color' => 'rose', 'active' => true, 'sort' => 5],
        ],
        'status_jaminan' => [
            ['value' => 'aktif', 'label' => 'Aktif', 'color' => 'emerald', 'active' => true, 'sort' => 1],
            ['value' => 'tamat-tempoh', 'label' => 'Tamat Tempoh', 'color' => 'amber', 'active' => true, 'sort' => 2],
            ['value' => 'dibebaskan', 'label' => 'Dibebaskan', 'color' => 'blue', 'active' => true, 'sort' => 3],
            ['value' => 'dilucuthak', 'label' => 'Dilucuthak', 'color' => 'rose', 'active' => true, 'sort' => 4],
        ],
        'status_kutipan' => [
            ['value' => 'belum-dikunjungi', 'label' => 'Belum Dikunjungi', 'color' => 'slate', 'active' => true, 'sort' => 1],
            ['value' => 'dalam-tindakan', 'label' => 'Dalam Tindakan Kutipan', 'color' => 'amber', 'active' => true, 'sort' => 2],
            ['value' => 'janji-bayar', 'label' => 'Janji Bayar', 'color' => 'blue', 'active' => true, 'sort' => 3],
            ['value' => 'berjaya', 'label' => 'Berjaya', 'color' => 'emerald', 'active' => true, 'sort' => 4],
            ['value' => 'gagal', 'label' => 'Gagal', 'color' => 'rose', 'active' => true, 'sort' => 5],
            ['value' => 'sedia-litigasi', 'label' => 'Sedia untuk Tindakan Undang-Undang', 'color' => 'rose', 'active' => true, 'sort' => 6],
            ['value' => 'berjaya-dihubungi', 'label' => 'Berjaya Dihubungi', 'color' => 'emerald', 'active' => true, 'sort' => 7],
            ['value' => 'gagal-hubungi', 'label' => 'Gagal Hubungi', 'color' => 'amber', 'active' => true, 'sort' => 8],
            ['value' => 'rujuk-litigasi', 'label' => 'Perlu Rujuk Litigasi', 'color' => 'rose', 'active' => true, 'sort' => 9],
        ],
        'status_usahawan' => [
            ['value' => 'aktif', 'label' => 'Aktif', 'color' => 'emerald', 'active' => true, 'sort' => 1],
            ['value' => 'tidak-aktif', 'label' => 'Tidak Aktif', 'color' => 'slate', 'active' => true, 'sort' => 2],
            ['value' => 'digantung', 'label' => 'Digantung', 'color' => 'amber', 'active' => true, 'sort' => 3],
        ],
        'status_bayaran' => [
            ['value' => 'menunggu', 'label' => 'Menunggu', 'color' => 'amber', 'active' => true, 'sort' => 1],
            ['value' => 'berjaya', 'label' => 'Berjaya', 'color' => 'emerald', 'active' => true, 'sort' => 2],
            ['value' => 'gagal', 'label' => 'Gagal', 'color' => 'rose', 'active' => true, 'sort' => 3],
            ['value' => 'dibatalkan', 'label' => 'Dibatalkan', 'color' => 'slate', 'active' => true, 'sort' => 4],
            ['value' => 'dikembalikan', 'label' => 'Dikembalikan', 'color' => 'blue', 'active' => true, 'sort' => 5],
        ],
    ],
];
