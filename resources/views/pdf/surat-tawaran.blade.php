<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Surat Tawaran Kemudahan Pembiayaan TEKUN Niaga</title>
    <style>
        @page { margin: 1.4cm 2cm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.35;
            color: #111827;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo {
            height: 46px;
            margin-bottom: 4px;
        }
        .org-name {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .org-meta {
            font-size: 9pt;
            color: #374151;
            margin-top: 2px;
            line-height: 1.25;
        }
        .meta-row {
            margin-top: 8px;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .recipient {
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .recipient-name {
            font-weight: bold;
        }
        .subject {
            font-weight: bold;
            text-transform: uppercase;
            margin: 8px 0 6px;
        }
        p { margin: 0 0 6px 0; text-align: justify; }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 8px 0;
        }
        .details-table td {
            padding: 2px 0;
            vertical-align: top;
            line-height: 1.3;
        }
        .details-table td:first-child {
            width: 34%;
            font-weight: bold;
        }
        .closing {
            margin-top: 8px;
        }
        .closing p {
            margin-bottom: 4px;
        }
        .signature-block {
            margin-top: 14px;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px dotted #111827;
            height: 16px;
            margin-bottom: 4px;
        }
        .signature-label {
            font-weight: bold;
            font-size: 9.5pt;
        }
        .signature-meta {
            margin-top: 4px;
            font-size: 9.5pt;
            line-height: 1.25;
        }
    </style>
</head>
<body>
    <div class="header">
        @if ($logoBase64)
            <img src="{{ $logoBase64 }}" alt="TEKUN Nasional" class="logo">
        @else
            <div style="font-size: 10pt; color: #6b7280; margin-bottom: 8px;">[LOGO TEKUN NASIONAL]</div>
        @endif
        <div class="org-name">TEKUN NASIONAL</div>
        <div class="org-meta">{{ $branchAddress }}</div>
        <div class="org-meta">{{ $branchPhone }} / {{ $branchEmail }}</div>
    </div>

    <div class="meta-row">
        <div>Rujukan Kami: {{ $referenceNo }}</div>
        <div>Tarikh: {{ $letterDate }}</div>
    </div>

    <div class="recipient">
        <div class="recipient-name">{{ $applicantName }}</div>
        <div>{{ $applicantIc }}</div>
        <div>{{ $applicantAddress }}</div>
    </div>

    <div>Tuan/Puan,</div>

    <div class="subject">TAWARAN KEMUDAHAN PEMBIAYAAN TEKUN NIAGA</div>

    <p>Dengan hormatnya perkara di atas adalah dirujuk.</p>

    <p>
        2. Sukacita dimaklumkan bahawa permohonan kemudahan pembiayaan TEKUN Niaga tuan/puan telah
        diluluskan oleh Jawatankuasa Pembiayaan dengan butiran seperti berikut:
    </p>

    <table class="details-table">
        <tr>
            <td>Skim Pembiayaan</td>
            <td>: {{ $financingScheme }}</td>
        </tr>
        <tr>
            <td>Jumlah Pembiayaan</td>
            <td>: {{ $approvedAmount }}</td>
        </tr>
        <tr>
            <td>Tempoh Pembiayaan</td>
            <td>: {{ $tenureLabel }}</td>
        </tr>
        <tr>
            <td>Kadar Keuntungan</td>
            <td>: {{ $profitRateLabel }}</td>
        </tr>
        <tr>
            <td>Ansuran Bulanan</td>
            <td>: {{ $monthlyInstallment }}</td>
        </tr>
        <tr>
            <td>Konsep Syariah</td>
            <td>: {{ $syariahConcept }}</td>
        </tr>
    </table>

    <p>
        3. Tawaran ini tertakluk kepada Terma dan Syarat Perjanjian Pembiayaan. Bayaran ansuran
        pertama perlu dibuat pada bulan selepas dana pembiayaan dikeluarkan.
    </p>

    <p>
        4. Sila tandatangani Surat Penerimaan Tawaran dan Perjanjian Pembiayaan, dan kembalikan
        ke Pejabat Cawangan TEKUN dalam tempoh {{ $acceptanceDays }} hari dari tarikh surat ini.
    </p>

    <div class="closing">
        <p>Kerjasama dan perhatian tuan/puan didahului dengan ucapan terima kasih.</p>
        <p>Sekian.</p>
        <p>Yang benar,</p>
    </div>

    <div class="signature-block">
        <div class="signature-line"></div>
        <div class="signature-label">(Tandatangan Pegawai TEKUN)</div>
        <div class="signature-meta">
            @if ($signatoryName !== '')
                <div>Nama: {{ $signatoryName }}</div>
            @else
                <div>Nama:</div>
            @endif
            <div>Jawatan: {{ $signatoryTitle }}</div>
            <div>TEKUN Nasional</div>
        </div>
    </div>
</body>
</html>
