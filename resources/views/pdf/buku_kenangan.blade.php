<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Kenangan Wisuda - {{ $periode->nama_periode }}</title>
    <style>
        @page {
            margin: 1.0cm 1.4cm 1.8cm 1.4cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.35;
            font-size: 10.5px;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        
        /* Cover Styling */
        .cover {
            text-align: center;
            padding-top: 60px;
            page-break-after: always;
        }
        .cover-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }
        .institution-name {
            font-size: 16px;
            font-weight: bold;
            color: #4338ca;
            margin-bottom: 25px;
        }
        .cover-subtitle {
            font-size: 14px;
            color: #334155;
            font-weight: 600;
            margin-bottom: 30px;
        }
        .cover-badge {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px auto;
            border-radius: 50%;
            background-color: #4338ca;
            color: #ffffff;
            font-size: 36px;
            line-height: 100px;
            font-weight: bold;
        }
        .cover-meta {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 40px;
        }

        /* Kaprodi Section / Executive Separator Page */
        .kaprodi-page {
            text-align: center;
            padding-top: 75px;
            page-break-after: always;
        }
        .kaprodi-badge-prodi {
            display: inline-block;
            background-color: #312e81;
            color: #ffffff;
            font-size: 11.5px;
            font-weight: bold;
            padding: 5px 20px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
        }
        .kaprodi-prodi-title {
            font-size: 23px;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 6px 0;
            line-height: 1.25;
        }
        .kaprodi-prodi-gelar {
            font-size: 13.5px;
            color: #4338ca;
            font-weight: bold;
            margin-bottom: 24px;
        }
        .kaprodi-photo-container {
            width: 300px;
            height: 400px;
            margin: 0 auto 24px auto;
            background-color: #ffffff;
            border: 4px solid #ffffff;
            outline: 2px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }
        .kaprodi-photo-container img {
            width: 300px;
            height: 400px;
            display: block;
        }
        .kaprodi-title-label {
            font-size: 12px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
        }
        .kaprodi-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: 0.3px;
        }
        .kaprodi-nip {
            font-size: 12px;
            color: #475569;
            font-family: monospace;
            font-weight: bold;
        }
        .kaprodi-stats-box {
            margin-top: 25px;
        }
        .kaprodi-stats {
            padding: 6px 22px;
            background-color: #e0e7ff;
            color: #3730a3;
            border-radius: 20px;
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* Section Header in Wisudawan Pages */
        .section-header {
            background-color: #312e81;
            color: #ffffff;
            padding: 7px 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 5px;
            margin-top: 0;
            margin-bottom: 8px;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        /* Candidate Card Grid */
        .candidate-card {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 7px;
            background-color: #ffffff;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .candidate-card td.photo-cell {
            width: 80px;
            padding: 7px 0 7px 7px;
            vertical-align: top;
        }
        .photo-box {
            width: 80px;
            height: 108px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            text-align: center;
            overflow: hidden;
        }
        .photo-box img {
            width: 80px;
            height: 108px;
            display: block;
        }
        .candidate-card td.details-cell {
            padding: 7px 10px;
            vertical-align: top;
        }
        .candidate-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 3px 0;
            line-height: 1.2;
        }
        .candidate-meta {
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 4px;
        }
        .meta-tag {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-family: monospace;
        }
        .candidate-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            color: #334155;
            margin-top: 2px;
        }
        .candidate-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .candidate-table td.label-col {
            width: 55px;
            font-weight: bold;
            color: #475569;
            white-space: nowrap;
        }
        .candidate-table td.colon-col {
            width: 7px;
            text-align: center;
            font-weight: bold;
            color: #64748b;
        }
        .candidate-table td.val-col {
            color: #1e293b;
        }

        /* Document Footer Image */
        footer.image-footer {
            position: fixed;
            bottom: -1.8cm;
            left: -1.4cm;
            right: -1.4cm;
            text-align: center;
            z-index: 100;
        }
        footer.image-footer img {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>
<body>

    @if(!empty($footerImagePath))
    <footer class="image-footer">
        <img src="{{ $footerImagePath }}" alt="Footer Dokumen">
    </footer>
    @endif

    <!-- 1. Cover Page -->
    <div class="cover">
        <div class="cover-badge">P</div>
        <h1 class="cover-title">BUKU KENANGAN WISUDA</h1>
        <div class="institution-name">POLITEKNIK INDONUSA SURAKARTA</div>
        <div class="cover-subtitle">{{ $periode->nama_periode }}</div>
        <p style="font-size: 13px; font-weight: bold; color: #334155;">Tahun Akademik {{ $periode->tahun_akademik }}</p>
        
        <div class="cover-meta">
            <p>Tanggal Pelaksanaan: {{ \Carbon\Carbon::parse($periode->tanggal_pelaksanaan)->isoFormat('DD MMMM YYYY') }}</p>
            <p>Total Wisudawan: {{ $totalPeserta }} Peserta</p>
        </div>
    </div>

    <!-- 2. Per-Program Studi Sequence: (1 Page Kaprodi -> N Pages Wisudawan List) -->
    @foreach($groupedByProdi as $item)
        <!-- HALAMAN 1: Foto & Profil Ketua Program Studi (Kaprodi) -->
        <div class="kaprodi-page">
            <div class="kaprodi-badge-prodi">
                {{ $item['jenjang_label'] }}
            </div>
            <h2 class="kaprodi-prodi-title">{{ $item['nama_prodi_clean'] }}</h2>
            @if($item['gelar'])
                <div class="kaprodi-prodi-gelar">Gelar Kelulusan: {{ $item['gelar'] }}</div>
            @else
                <div style="margin-bottom: 24px;"></div>
            @endif

            <div class="kaprodi-photo-container">
                @if(!empty($item['kaprodi_foto_path']) && file_exists($item['kaprodi_foto_path']))
                    <img src="{{ $item['kaprodi_foto_path'] }}" alt="Foto Kaprodi {{ $item['nama_prodi'] }}">
                @elseif(!empty($defaultFotoPath) && file_exists($defaultFotoPath))
                    <img src="{{ $defaultFotoPath }}" alt="Foto Kaprodi Default">
                @elseif(file_exists(public_path('images/default_toga_silhouette.png')))
                    <img src="{{ public_path('images/default_toga_silhouette.png') }}" alt="Foto Kaprodi Default">
                @else
                    <div style="line-height: 400px; font-size: 13px; color: #94a3b8;">[ Foto Kaprodi ]</div>
                @endif
            </div>

            <div class="kaprodi-title-label">Ketua Program Studi</div>
            <h3 class="kaprodi-name">{{ $item['kaprodi_nama'] ?: 'Ketua Program Studi ' . $item['nama_prodi'] }}</h3>
            @if($item['kaprodi_nip'])
                <div class="kaprodi-nip">NIP / NIDN: {{ $item['kaprodi_nip'] }}</div>
            @endif

            <div class="kaprodi-stats-box">
                <div class="kaprodi-stats">
                    TOTAL WISUDAWAN: {{ $item['wisudawans']->count() }} PESERTA
                </div>
            </div>
        </div>

        <!-- HALAMAN BERIKUTNYA: Data Wisudawan Program Studi (Maks 6 per halaman) -->
        @foreach($item['wisudawans']->chunk(6) as $chunkIndex => $pageChunk)
            <div class="section-header">
                DAFTAR LULUSAN &mdash; {{ $item['jenjang_label'] }} {{ $item['nama_prodi_clean'] }}
                @if($item['wisudawans']->count() > 6)
                    (HALAMAN {{ $chunkIndex + 1 }} DARI {{ ceil($item['wisudawans']->count() / 6) }})
                @endif
            </div>

            @foreach($pageChunk as $w)
                <table class="candidate-card">
                    <tr>
                        <td class="photo-cell">
                            <div class="photo-box">
                                @if($w->pas_foto && file_exists(public_path('storage/' . $w->pas_foto)))
                                    <img src="{{ public_path('storage/' . $w->pas_foto) }}" alt="Pas Foto">
                                @elseif(!empty($defaultFotoPath) && file_exists($defaultFotoPath))
                                    <img src="{{ $defaultFotoPath }}" alt="Siluet Wisudawan">
                                @elseif(file_exists(public_path('images/default_toga_silhouette.png')))
                                    <img src="{{ public_path('images/default_toga_silhouette.png') }}" alt="Siluet Wisudawan">
                                @else
                                    <div style="line-height: 108px; font-size: 9px; color: #94a3b8;">[ Pas Foto ]</div>
                                @endif
                            </div>
                        </td>
                        <td class="details-cell">
                            <h3 class="candidate-name">{{ $w->nama_lengkap }}{{ $w->gelar ? ', ' . $w->gelar : '' }}</h3>
                            <div class="candidate-meta">
                                NIM: <span class="meta-tag">{{ $w->nim }}</span> &nbsp;|&nbsp;
                                TTL: <strong>{{ $w->ttl }}</strong> &nbsp;|&nbsp;
                                Orang Tua: <strong>{{ $w->orang_tua }}</strong>
                            </div>

                            <table class="candidate-table">
                                <tr>
                                    <td class="label-col">Alamat</td>
                                    <td class="colon-col">:</td>
                                    <td class="val-col">{{ $w->alamat ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="label-col">Pekerjaan</td>
                                    <td class="colon-col">:</td>
                                    <td class="val-col">{{ $w->pekerjaan ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="label-col">Judul TA</td>
                                    <td class="colon-col">:</td>
                                    <td class="val-col" style="font-style: italic;">"{{ $w->judul_ta ?: '-' }}"</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @endforeach

            @if(!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>

