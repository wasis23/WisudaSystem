<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>E-Ticket Wisuda - {{ $wisudawan->nama_lengkap }} ({{ $wisudawan->nim }})</title>
    <style>
        @page {
            margin: 0.8cm 1.0cm 0.8cm 1.0cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #0f172a;
            line-height: 1.25;
            font-size: 9.5px;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .page-break {
            page-break-after: always;
        }

        /* Modern Top Banner (No Kop Surat) */
        .event-header {
            background-color: #1e1b4b;
            color: #ffffff;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 10px;
        }
        .event-header table {
            width: 100%;
            border-collapse: collapse;
        }
        .event-header td {
            vertical-align: middle;
        }
        .event-badge {
            display: inline-block;
            background-color: #4338ca;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .event-title {
            font-size: 13.5px;
            font-weight: bold;
            color: #ffffff;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .event-subtitle {
            font-size: 9px;
            color: #c7d2fe;
            margin-top: 2px;
        }
        .event-meta {
            text-align: right;
            font-size: 8.5px;
            color: #e0e7ff;
            line-height: 1.35;
        }
        .event-meta strong {
            color: #ffffff;
        }

        /* Ticket Cards */
        .ticket-card {
            width: 100%;
            border: 1.5px dashed #cbd5e1;
            border-radius: 8px;
            margin-bottom: 8px;
            background-color: #ffffff;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .ticket-card td {
            padding: 0;
            vertical-align: top;
        }

        /* Left QR Section */
        .qr-col {
            width: 140px;
            background-color: #f8fafc;
            border-right: 1.5px dashed #cbd5e1;
            padding: 8px;
            text-align: center;
        }
        .qr-badge {
            font-size: 7.5px;
            font-weight: bold;
            color: #ffffff;
            padding: 2px 4px;
            border-radius: 3px;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }
        .bg-student {
            background-color: #4338ca;
        }
        .bg-guest-1 {
            background-color: #2563eb;
        }
        .bg-guest-2 {
            background-color: #7c3aed;
        }
        .bg-guest-extra {
            background-color: #0d9488;
        }

        .qr-img-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px;
            display: inline-block;
            margin-bottom: 3px;
        }
        .qr-img-box img {
            width: 100px;
            height: 100px;
            display: block;
        }
        .qr-token-text {
            font-family: 'Courier', monospace;
            font-size: 8px;
            font-weight: bold;
            color: #334155;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* Right Details Section */
        .details-col {
            padding: 8px 12px;
            vertical-align: top;
        }
        .ticket-type-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .person-name {
            font-size: 12.5px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
            line-height: 1.2;
        }
        .person-meta {
            font-size: 9px;
            color: #475569;
            margin-bottom: 6px;
        }
        .meta-pill {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-family: monospace;
            font-size: 8.5px;
        }

        /* Details Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-top: 4px;
        }
        .info-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .info-table td.lbl {
            width: 80px;
            font-weight: bold;
            color: #64748b;
        }
        .info-table td.colon {
            width: 8px;
            text-align: center;
            color: #94a3b8;
        }
        .info-table td.val {
            color: #1e293b;
        }

        /* Access Rights Box */
        .access-box {
            margin-top: 6px;
            padding: 4px 8px;
            background-color: #f1f5f9;
            border-radius: 4px;
            font-size: 7.8px;
            color: #334155;
            line-height: 1.3;
        }
        .access-box strong {
            color: #0f172a;
        }

        /* Footer Instructions Box */
        .instructions-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            margin-top: 6px;
            font-size: 7.5px;
            color: #64748b;
            line-height: 1.35;
        }
        .instructions-box strong {
            color: #334155;
        }
        .instructions-list {
            margin: 2px 0 0 0;
            padding-left: 14px;
        }
        .instructions-list li {
            margin-bottom: 1px;
        }
    </style>
</head>
<body>

    <!-- Header Event Banner (Clean, Modern, No Kop Surat) -->
    <div class="event-header">
        <table>
            <tr>
                <td style="width: 65%;">
                    <div class="event-badge">E-TICKET RESMI & PASS MASUK WISUDA</div>
                    <h1 class="event-title">WISUDA POLITEKNIK INDONUSA SURAKARTA</h1>
                    <div class="event-subtitle">
                        {{ $periode->nama_periode }} &bull; Tahun Akademik {{ $periode->tahun_akademik }}
                    </div>
                </td>
                <td class="event-meta" style="width: 35%;">
                    <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($periode->tanggal_pelaksanaan)->isoFormat('dddd, DD MMMM YYYY') }}</div>
                    <div><strong>Waktu:</strong> 07.00 WIB s/d Selesai</div>
                    <div><strong>Total Tiket:</strong> {{ 1 + count($guestTickets) }} Kartu (1 Wisudawan + {{ count($guestTickets) }} Tamu)</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- TICKET 1: WISUDAWAN UTAMA -->
    <table class="ticket-card">
        <tr>
            <td class="qr-col">
                <span class="qr-badge bg-student">KARTU WISUDAWAN</span>
                <div class="qr-img-box">
                    <img src="{{ $studentQrBase64 }}" alt="QR Mahasiswa">
                </div>
                <div class="qr-token-text">{{ $wisudawan->qr_code_token }}</div>
            </td>
            <td class="details-col">
                <div class="ticket-type-label">CALON WISUDAWAN / PESERTA UTAMA</div>
                <h2 class="person-name">{{ $wisudawan->nama_lengkap }}{{ $wisudawan->gelar ? ', ' . $wisudawan->gelar : '' }}</h2>
                <div class="person-meta">
                    NIM: <span class="meta-pill">{{ $wisudawan->nim }}</span> &nbsp;&bull;&nbsp;
                    Program Studi: <strong>{{ $wisudawan->programStudi?->nama_prodi }} ({{ $wisudawan->programStudi?->jenjang }})</strong>
                </div>

                <table class="info-table">
                    <tr>
                        <td class="lbl">Orang Tua / Wali</td>
                        <td class="colon">:</td>
                        <td class="val">{{ $wisudawan->orang_tua ?: ($wisudawan->nama_ayah . ' & ' . $wisudawan->nama_ibu) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Status Keuangan</td>
                        <td class="colon">:</td>
                        <td class="val" style="color: #15803d; font-weight: bold;">LUNAS & TERVERIFIKASI</td>
                    </tr>
                    <tr>
                        <td class="lbl">Hak Kursi Tamu</td>
                        <td class="colon">:</td>
                        <td class="val"><strong>{{ count($guestTickets) }} Orang Pendamping</strong> (+ 1 Wisudawan)</td>
                    </tr>
                </table>

                <div class="access-box">
                    <strong>HAK AKSES PRESENSI:</strong> Berlaku untuk <strong>2x Scan</strong> (1. Gate Keamanan Halaman Depan & 2. Pintu Masuk Venue). Tiket ini memicu pemanggilan nama di layar panggung prosesi.
                </div>
            </td>
        </tr>
    </table>

    <!-- TICKET CARDS: GUEST TICKETS -->
    @foreach($guestTickets as $index => $gt)
        <table class="ticket-card">
            <tr>
                <td class="qr-col">
                    <span class="qr-badge {{ $index === 0 ? 'bg-guest-1' : ($index === 1 ? 'bg-guest-2' : 'bg-guest-extra') }}">
                        PENDAMPING #{{ $index + 1 }} {{ $index >= 2 ? '(EKSTRA SIKEU)' : '' }}
                    </span>
                    <div class="qr-img-box">
                        <img src="{{ $gt['qr_base64'] }}" alt="QR Tamu">
                    </div>
                    <div class="qr-token-text">{{ $gt['qr_guest_token'] }}</div>
                </td>
                <td class="details-col">
                    <div class="ticket-type-label">TAMU UNDANGAN / PENDAMPING WISUDAWAN</div>
                    <h2 class="person-name">{{ $gt['nama_tamu'] ?: ('Pendamping ' . ($index + 1)) }}</h2>
                    <div class="person-meta">
                        Hubungan: <strong>{{ $gt['hubungan'] ?: 'Tamu Undangan' }}</strong>
                    </div>

                    <table class="info-table">
                        <tr>
                            <td class="lbl">Mendampingi</td>
                            <td class="colon">:</td>
                            <td class="val"><strong>{{ $wisudawan->nama_lengkap }}</strong> (NIM: {{ $wisudawan->nim }})</td>
                        </tr>
                        <tr>
                            <td class="lbl">Program Studi</td>
                            <td class="colon">:</td>
                            <td class="val">{{ $wisudawan->programStudi?->nama_prodi }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Hak Konsumsi</td>
                            <td class="colon">:</td>
                            <td class="val" style="color: #b45309; font-weight: bold;">1 Porsi Snack & Kursi Tamu</td>
                        </tr>
                    </table>

                    <div class="access-box">
                        <strong>HAK AKSES PRESENSI:</strong> Berlaku untuk scan masuk Gate Halaman Depan & Pengambilan Konsumsi (*Snack Box*) di meja penerima tamu auditorium.
                    </div>
                </td>
            </tr>
        </table>
    @endforeach

    <!-- Petunjuk & Tata Tertib (Compact Bottom Box) -->
    <div class="instructions-box">
        <strong>TATA TERTIB & PETUNJUK PENGGUNAAN E-TICKET:</strong>
        <ol class="instructions-list">
            <li>Tunjukkan Barcode / QR Code pada masing-masing kartu kepada petugas scanner saat memasuki area kampus & ballroom.</li>
            <li>1 Kartu Barcode hanya berlaku untuk 1 orang (Mahasiswa / Pendamping masing-masing membawa kartu tersendiri).</li>
            <li>E-Ticket ini dapat dicetak pada kertas A4 atau cukup ditunjukkan langsung melalui layar ponsel (*smartphone*).</li>
        </ol>
    </div>

</body>
</html>
