<?php

return [
    'negeriOptions' => [
        'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis',
        'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu',
        'Wilayah Persekutuan Kuala Lumpur', 'Wilayah Persekutuan Labuan', 'Wilayah Persekutuan Putrajaya',
    ],
    'jenisPerniagaanOptions' => [
        'Peruncitan', 'Pemborongan', 'Pembuatan', 'Perkhidmatan', 'Pertanian', 'Penternakan',
        'Perikanan', 'Makanan & Minuman', 'Fesyen', 'Teknologi', 'Lain-lain',
    ],
    'statusPerniagaanOptions' => [
        ['value' => 'sedang_berniaga', 'label' => 'Sedang Berniaga'],
        ['value' => 'memulakan', 'label' => 'Memulakan Perniagaan'],
    ],
    'sektorPerniagaanOptions' => [
        'Pertanian', 'Perusahaan Asas Tani', 'Peruncitan', 'Perkhidmatan', 'Pembuatan', 'Kontraktor Kecil', 'Lain-lain',
    ],
    'jantinaOptions' => [
        ['value' => 'L', 'label' => 'Lelaki'],
        ['value' => 'P', 'label' => 'Wanita'],
    ],
    'agamaOptions' => [
        ['value' => 'islam', 'label' => 'Islam'],
        ['value' => 'buddha', 'label' => 'Buddha'],
        ['value' => 'hindu', 'label' => 'Hindu'],
        ['value' => 'kristian', 'label' => 'Kristian'],
        ['value' => 'lain_lain', 'label' => 'Lain-lain'],
    ],
    'tarafPerkahwinanOptions' => ['Bujang', 'Berkahwin', 'Duda', 'Janda'],
    'bangsaOptions' => ['Cina', 'India', 'Melayu', 'Orang Asli', 'TIADA MAKLUMAT', 'Lain-lain'],
    'tarafPendidikanOptions' => [
        'PHD / Ijazah Sarjana', 'Ijazah Sarjana Muda', 'Diploma', 'STPM', 'SPM', 'PMR Setaraf', 'Sijil Setaraf', 'Lain-lain',
    ],
    'statusPekerjaanOptions' => [
        ['value' => 'bekerja', 'label' => 'Bekerja'],
        ['value' => 'tidak_bekerja', 'label' => 'Tidak Bekerja'],
    ],
    'sektorPekerjaanOptions' => ['Kerajaan', 'Swasta', 'Badan Berkanun', 'Kerja Sendiri', 'Lain-Lain'],
    'jawatanOptions' => ['Eksekutif', 'Pengurus', 'Kerani', 'Juruteknik', 'Operator', 'Pekerja Kasar', 'Lain-lain'],
    'statusJawatanOptions' => ['Tetap', 'Kontrak', 'Sementara'],
    'statusKediamanOptions' => ['Sendiri', 'Sewa', 'Keluarga'],
    'statusPremisOptions' => ['Sendiri', 'Sewa', 'Keluarga'],
    'pemilikanPerniagaanOptions' => ['Individu', 'Pemilikan Tunggal', 'Perkongsian', 'Sendirian Berhad'],
    'institusiPembiayaanOptions' => ['MARA', 'AIM', 'Lain-lain Agensi Kerajaan'],
    'kekerapanBayaranOptions' => ['Mingguan', 'Bulanan', 'Mengikut Tempoh Kontrak Kerja', 'Inden'],
    'agensKursusOptions' => ['INSKEN', 'SME CORP', 'CEDAR', 'Lain-lain'],
    'takafulKemalanganPakej' => [
        ['value' => 'pakej1', 'label' => 'RM 7.20/tahun (RM 1,000 - RM 30,000)', 'min' => 1000, 'max' => 30000],
        ['value' => 'pakej2', 'label' => 'RM 19.44/tahun (RM 30,001 - RM 50,000)', 'min' => 30001, 'max' => 50000],
        ['value' => 'pakej3', 'label' => 'RM 32.40/tahun (RM 50,001 - RM 100,000)', 'min' => 50001, 'max' => 100000],
    ],
    'perkesoPakej' => [
        ['value' => 'a', 'label' => 'RM 157.20/tahun', 'amount' => 157.2],
        ['value' => 'b', 'label' => 'RM 442.80/tahun', 'amount' => 442.8],
        ['value' => 'c', 'label' => 'RM 232.80/tahun', 'amount' => 232.8],
        ['value' => 'd', 'label' => 'RM 592.80/tahun', 'amount' => 592.8],
    ],
    'kategoriPembiayaanOptions' => [
        'TEKUN Niaga', 'TEMAN TEKUN', 'Kontrak', 'SPUMI', 'BPU', 'TEKUN Corp', 'Lain-lain',
    ],
    'mykadSampleFrontIdentity' => [
        'ic' => '691115-12-5053',
        'name' => 'MASRI BIN YAKOP',
    ],
];
