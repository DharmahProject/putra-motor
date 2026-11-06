<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi {{ $nomor }}</title>
    <style>
        @page { margin: 28px 32px 72px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #3c2a12; font-family: Helvetica, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        .brand td { vertical-align: middle; padding: 0; }
        .brand-mark { width: 86px; }
        .brand-logo { width: 78px; height: auto; }
        .brand td.brand-cell { padding-left: 8px; text-align: center; }
        .brand-title { color: #806432; font-size: 28px; font-weight: bold; line-height: 1; text-align: center; white-space: nowrap; }
        .brand-sub { margin-top: 4px; color: #806432; font-size: 13px; font-weight: bold; text-align: center; }
        .tagline { margin-top: 8px; color: #806432; font-size: 11px; font-weight: bold; letter-spacing: .3px; }
        .address { margin-top: 6px; color: #5c4a30; font-size: 10px; line-height: 1.45; }
        .sheet { table-layout: fixed; }
        .split-left, .split-right { width: 50%; vertical-align: top; }
        .split-right { padding-left: 14px; border-left: 2px solid #A8874A; }
        .split-left { padding-right: 6px; }
        .header-row td { padding-bottom: 12px; }
        .party-cell { padding-top: 10px; padding-bottom: 10px; border-top: 1px solid #e0d0b4; }
        .doc-title { margin: 0 0 12px; color: #806432; font-size: 20px; font-weight: bold; letter-spacing: .4px; }
        .meta td { padding: 2px 0; font-size: 11px; vertical-align: top; }
        .meta .key { width: 92px; color: #5c4a30; }
        .meta .colon { width: 14px; }
        .meta .value { font-weight: bold; }
        .info td { padding: 5px 0; vertical-align: top; }
        .info .key { width: 78px; color: #5c4a30; }
        .info .colon { width: 12px; }
        .info .value { font-weight: bold; }
        .section-title { margin: 12px 0 8px; padding-top: 10px; border-top: 1px solid #e0d0b4; color: #806432; font-size: 16px; font-weight: bold; }
        .items th { padding: 7px 8px; color: #fff; background: #806432; font-size: 11px; text-align: left; }
        .items th.qty, .items td.qty { width: 52px; text-align: center; }
        .items th.money, .items td.money { width: 110px; text-align: right; }
        .items td { padding: 6px 8px; border-bottom: 1px solid #f3eadc; vertical-align: top; }
        .items tr.group td { padding: 6px 8px; color: #806432; background: #f6f0e6; font-size: 11px; font-style: italic; font-weight: bold; letter-spacing: .4px; }
        .note { color: #7a6544; font-size: 9px; }
        .summary { margin-top: 8px; }
        .summary .spacer { width: 48%; }
        .summary .label { color: #5c4a30; text-align: right; }
        .summary .colon { width: 16px; text-align: center; }
        .summary .amount { width: 120px; font-weight: bold; text-align: right; }
        .summary td { padding: 3px 0; }
        .grand td { padding-top: 8px; }
        .summary .grand .label,
        .summary .grand .colon,
        .summary .grand .amount { padding-top: 7px; padding-bottom: 7px; color: #fff; background: #A8874A; font-size: 12px; font-weight: bold; }
        .summary .grand .label { padding-left: 8px; }
        .bank-title { margin: 16px 0 6px; color: #806432; font-size: 13px; font-weight: bold; }
        .bank .key { width: 92px; color: #5c4a30; }
        .bank .colon { width: 14px; }
        .bank .value { font-weight: bold; }
        .bank td { padding: 2px 0; vertical-align: top; }
        .pay { margin-top: 14px; }
        .pay .key { width: 140px; color: #5c4a30; }
        .pay .colon { width: 14px; }
        .pay .value { font-weight: bold; }
        .pay td { padding: 2px 0; }
        .signs { margin-top: 52px; }
        .signs td { width: 50%; text-align: center; vertical-align: top; }
        .sign-label { color: #806432; font-size: 12px; font-weight: bold; }
        .sign-name { margin-top: 68px; color: #3c2a12; font-size: 12px; font-weight: bold; }
        .sign-line { width: 180px; margin: 6px auto 0; border-bottom: 1px dotted #806432; }
        .thanks { position: fixed; bottom: -48px; left: 0; right: 0; padding-top: 8px; border-top: 3px solid #A8874A; color: #806432; font-size: 10px; font-style: italic; text-align: center; line-height: 1.5; }
    </style>
</head>
<body>
    <table class="sheet">
        <tr class="header-row">
            <td class="split-left">
                <table class="brand">
                    <tr>
                        <td class="brand-mark">
                            @if($logo)
                                <img class="brand-logo" src="{{ $logo }}" alt="Putra Motor">
                            @endif
                        </td>
                        <td class="brand-cell">
                            <div class="brand-title">PUTRA MOTOR</div>
                            <div class="brand-sub">BENGKEL MOBIL</div>
                        </td>
                    </tr>
                </table>
                <div class="tagline">SPESIALIS NISSAN DATSUN DAN UMUM</div>
                <div class="address">
                    Jl. Raya Baru Pabuaran, RT.04/RW.03, Cibadak<br>
                    Tanah Sareal, Kota Bogor<br>
                    No. Telp. 089655187456
                </div>
            </td>
            <td class="split-right">
                <div class="doc-title">KWITANSI PEMBAYARAN</div>
                <table class="meta">
                    <tr>
                        <td class="key">No. Kwitansi</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $nomor }}</td>
                    </tr>
                    <tr>
                        <td class="key">Tanggal</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $tanggal }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="party-row">
            <td class="split-left party-cell">
                <table class="info">
                    <tr><td class="key">Pelanggan</td><td class="colon">:</td><td class="value">{{ $pelanggan }}</td></tr>
                    <tr><td class="key">No. HP</td><td class="colon">:</td><td class="value">{{ $telepon }}</td></tr>
                </table>
            </td>
            <td class="split-right party-cell">
                <table class="info">
                    <tr><td class="key">Kendaraan</td><td class="colon">:</td><td class="value">{{ $kendaraan }}</td></tr>
                    <tr><td class="key">No. Polisi</td><td class="colon">:</td><td class="value">{{ $plat }}</td></tr>
                    <tr><td class="key">Odometer</td><td class="colon">:</td><td class="value">{{ $odometer }}</td></tr>
                    <tr><td class="key">Advisor</td><td class="colon">:</td><td class="value">{{ $advisor }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Rincian Transaksi</div>
    <table class="items">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="qty">Qty</th>
                <th class="money">Harga</th>
                <th class="money">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $group)
                @if(count($group['items']))
                    <tr class="group"><td colspan="4">{{ $group['label'] }}</td></tr>
                    @foreach($group['items'] as $item)
                        <tr>
                            <td>
                                {{ $item['nama'] }}
                                @if($item['keterangan'])
                                    <div class="note">{{ $item['keterangan'] }}</div>
                                @endif
                            </td>
                            <td class="qty">{{ $item['qty'] }}</td>
                            <td class="money">Rp {{ $item['harga'] }}</td>
                            <td class="money">Rp {{ $item['jumlah'] }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        @foreach($groups as $group)
            <tr>
                <td class="spacer"></td>
                <td class="label">{{ $group['subtotal_label'] }}</td>
                <td class="colon">:</td>
                <td class="amount">Rp {{ $group['subtotal'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="spacer"></td>
            <td class="label">Diskon</td>
            <td class="colon">:</td>
            <td class="amount">Rp {{ $diskon }}</td>
        </tr>
        <tr class="grand">
            <td class="spacer"></td>
            <td class="label grand-bar">TOTAL BAYAR</td>
            <td class="colon grand-bar">:</td>
            <td class="amount grand-bar">Rp {{ $total }}</td>
        </tr>
    </table>

    <table class="pay">
        <tr><td class="key">Metode Pembayaran</td><td class="colon">:</td><td class="value">{{ $metode }}</td></tr>
        <tr><td class="key">Terbilang</td><td class="colon">:</td><td class="value">{{ $terbilang }}</td></tr>
    </table>

    <div class="bank-title">Informasi Pembayaran</div>
    <table class="bank">
        <tr><td class="key">Atas Nama</td><td class="colon">:</td><td class="value">{{ $banks['atas_nama'] }}</td></tr>
        <tr>
            <td class="key">No. Rekening</td>
            <td class="colon">:</td>
            <td class="value">
                @forelse($banks['rekening'] as $rekening)
                    {{ $rekening }}@if(! $loop->last)<br>@endif
                @empty
                    -
                @endforelse
            </td>
        </tr>
    </table>

    <table class="signs">
        <tr>
            <td>
                <div class="sign-label">Pelanggan</div>
                <div class="sign-name">{{ $pelanggan }}</div>
                <div class="sign-line"></div>
            </td>
            <td>
                <div class="sign-label">Kasir / Service Advisor</div>
                <div class="sign-name">{{ $advisor }}</div>
                <div class="sign-line"></div>
            </td>
        </tr>
    </table>

    <div class="thanks">
        Terima kasih telah mempercayakan kendaraan Anda kepada Putra Motor.<br>
        Simpan kwitansi ini sebagai bukti pembayaran yang sah.
    </div>
</body>
</html>
