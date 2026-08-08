<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Kenangan Wisuda - {{ $periode->nama_periode }}</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            line-height: 1.4;
            font-size: 11px;
        }
        .page-break {
            page-break-after: always;
        }
        
        /* Cover Styling */
        .cover {
            text-align: center;
            padding-top: 100px;
        }
        .cover-title {
            font-size: 26px;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        .institution-name {
            font-size: 18px;
            font-weight: bold;
            color: #4338ca;
            margin-bottom: 30px;
        }
        .cover-subtitle {
            font-size: 15px;
            color: #334155;
            font-weight: 600;
            margin-bottom: 40px;
        }
        .cover-badge {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px auto;
            border-radius: 50%;
            background-color: #4338ca;
            color: #ffffff;
            font-size: 40px;
            line-height: 120px;
            font-weight: bold;
        }
        .cover-meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 60px;
        }

        /* Header Section */
        .section-header {
            background-color: #312e81;
            color: #ffffff;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        /* Candidate Card Grid */
        .candidate-card {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            background-color: #ffffff;
        }
        .photo-box {
            width: 90px;
            height: 120px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            float: left;
            margin-right: 15px;
            text-align: center;
            overflow: hidden;
        }
        .photo-box img {
            width: 90px;
            height: auto;
            display: block;
        }
        .details-box {
            overflow: hidden;
        }
        .candidate-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        .candidate-meta {
            font-size: 10px;
            color: #475569;
            margin-bottom: 6px;
        }
        .meta-tag {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-family: monospace;
        }
        .ta-title {
            font-size: 10px;
            font-style: italic;
            color: #334155;
            margin-bottom: 6px;
        }
        .quote-box {
            background-color: #f8fafc;
            border-left: 3px solid #6366f1;
            padding: 6px 10px;
            font-size: 9.5px;
            color: #475569;
            border-radius: 0 4px 4px 0;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>

    <!-- Cover Page -->
    <div class="cover">
        <div class="cover-badge">P</div>
        <h1 class="cover-title">BUKU KENANGAN WISUDA</h1>
        <div class="institution-name">POLITEKNIK INDONUSA SURAKARTA</div>
        <div class="cover-subtitle">{{ $periode->nama_periode }}</div>
        <p style="font-size: 14px; font-weight: bold; color: #334155;">Tahun Akademik {{ $periode->tahun_akademik }}</p>
        
        <div class="cover-meta">
            <p>Tanggal Pelaksanaan: {{ \Carbon\Carbon::parse($periode->tanggal_pelaksanaan)->isoFormat('DD MMMM YYYY') }}</p>
            <p>Total Wisudawan: {{ $totalPeserta }} Peserta</p>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- Graduates Section Grouped by Prodi -->
    @foreach($groupedByProdi as $prodiName => $wisudawans)
        <div class="section-header">
            PROGRAM STUDI {{ $prodiName }}
        </div>

        @foreach($wisudawans as $w)
            <div class="candidate-card clearfix">
                <div class="photo-box">
                    @if($w->pas_foto && file_exists(public_path('storage/' . $w->pas_foto)))
                        <img src="{{ public_path('storage/' . $w->pas_foto) }}" alt="Pas Foto">
                    @else
                        <div style="line-height: 120px; font-size: 9px; color: #94a3b8;">[ Pas Foto ]</div>
                    @endif
                </div>

                <div class="details-box">
                    <h3 class="candidate-name">{{ $w->nama_lengkap }}</h3>
                    <div class="candidate-meta">
                        NIM: <span class="meta-tag">{{ $w->nim }}</span> &nbsp;|&nbsp;
                        IPK: <strong>{{ $w->ipk }}</strong> ({{ $w->predikat_kelulusan }}) &nbsp;|&nbsp;
                        Tgl Lulus: {{ \Carbon\Carbon::parse($w->tanggal_lulus)->format('d/m/Y') }}
                    </div>

                    <div class="ta-title">
                        <strong>Judul TA:</strong> "{{ $w->judul_ta }}"
                    </div>
                </div>
            </div>
        @endforeach

        <div class="page-break"></div>
    @endforeach

</body>
</html>
