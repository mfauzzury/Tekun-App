<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>{{ $label }} — {{ $rujukan }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12pt; color: #1e293b; margin: 40px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #166534; padding-bottom: 16px; }
        .header h1 { color: #166534; font-size: 16pt; margin: 0; }
        .header p { margin: 4px 0; font-size: 10pt; color: #64748b; }
        .meta { margin: 24px 0; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 4px 8px; vertical-align: top; }
        .meta td:first-child { width: 160px; font-weight: 600; color: #475569; }
        .body { margin: 24px 0; text-align: justify; }
        .highlight { background: #fef3c7; padding: 12px 16px; border-left: 4px solid #d97706; margin: 16px 0; }
        .footer { margin-top: 40px; font-size: 10pt; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 16px; }
        .sign { margin-top: 48px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TEKUN NASIONAL</h1>
        <p>{{ $cawangan }}</p>
        <p><strong>{{ $label }}</strong></p>
        <p>Rujukan: {{ $rujukan }}</p>
    </div>

    <div class="meta">
        <table>
            <tr><td>Tarikh</td><td>: {{ $tarikh }}</td></tr>
            <tr><td>Kepada</td><td>: {{ $nama }}</td></tr>
            <tr><td>No. Kad Pengenalan</td><td>: {{ $ic }}</td></tr>
            <tr><td>No. Akaun Pembiayaan</td><td>: {{ $noAkaun }}</td></tr>
            <tr><td>Klasifikasi Akaun</td><td>: {{ $klasifikasi }}</td></tr>
        </table>
    </div>

    <div class="body">
        <p>Dengan hormatnya perkara di atas adalah dirujuk.</p>
        <p>
            Sukacita dimaklumkan bahawa akaun pembiayaan tuan/puan di bawah TEKUN Nasional
            telah mencatatkan tunggakan bayaran balik. Butiran adalah seperti berikut:
        </p>

        <div class="highlight">
            <strong>Jumlah Tunggakan:</strong> RM {{ $tunggakan }}<br>
            <strong>Hari Lewat:</strong> {{ $hariLewat }} hari
        </div>

        <p>
            Tuan/puan diminta untuk menjelaskan bayaran tertunggak dalam tempoh <strong>14 hari</strong>
            dari tarikh surat ini. Kegagalan berbuat demikian akan menyebabkan tindakan lanjut
            termasuk penghantaran notis seterusnya dan rujukan ke unit kutipan / undang-undang.
        </p>

        <p>Sebarang pertanyaan, sila hubungi cawangan {{ $cawangan }}.</p>
    </div>

    <div class="sign">
        <p>Yang benar,</p>
        <p><strong>Pengurus Cawangan TEKUN</strong><br>{{ $cawangan }}</p>
    </div>

    <div class="footer">
        <p>Dokumen ini dijana secara automatik oleh Sistem Pengurusan Pembiayaan TEKUN (SPPT).</p>
    </div>
</body>
</html>
