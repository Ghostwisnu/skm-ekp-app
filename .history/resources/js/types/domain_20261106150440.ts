export type Role = 'admin' | 'operator' | 'keuangan';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type Option = { id: number; plat?: string; nama?: string };

export type Client = {
    id: number;
    nama: string;
    alamat: string | null;
    npwp: string | null;
    kontak: string | null;
    goods?: ClientGood[];
};

export type ClientGood = {
    id: number;
    client_id: number;
    jenis_barang: string;
    pricing_mode: 'per_qty' | 'flat_trip';
    harga: number;
};

export type Vehicle = {
    id: number;
    plat: string;
    jenis_kendaraan: string | null;
    model_kendaraan: string | null;
    nama_supir: string | null;
    no_hp_supir: string | null;
    keterangan: string | null;
};

export type TripItem = {
    id?: number;
    client_good_id: number | null;
    jenis_barang: string;
    rute: string | null;
    qty_barang: number | string | null;
    pricing_mode: 'per_qty' | 'flat_trip' | null;
    harga_satuan: number | null;
    total_harga: number | null;
    tonase_kg: number | string;
};

export type Trip = {
    id: number;
    tanggal: string;
    vehicle_id: number;
    client_id: number;
    destination_client_id: number | null;
    invoice_id: number | null;
    sopir: string;
    no_surat_jalan: string;
    total_qty?: number | null;
    vehicle?: Vehicle;
    client?: Client;
    destination_client?: Client;
    items?: TripItem[];
};










export type DailyCost = {
    id: number;
    tanggal: string;
    no_plat: string;
    no_surat_jalan: string;
    uang_jalan: number;
};








export type InvoiceLine = {
    id: number;
    no_surat_jalan: string;
    tanggal: string;
    plat: string;
    jenis_barang: string;
    rute: string | null;
    qty_barang: number | null;
    pricing_mode: 'per_qty' | 'flat_trip' | null;
    harga_satuan: number | null;
    tonase_kg: number;
    harga_per_kg: number;
    jumlah: number;
};

export type Invoice = {
    id: number;
    no_invoice: string;
    client_id: number;
    destination_client_id: number | null;
    tanggal_awal: string;
    tanggal_akhir: string;
    ppn_persen: number;
    subtotal: number;
    ppn_nominal: number;
    total: number;
    status: 'draft' | 'final';
    client?: Client;
    destination_client?: Client | null;
    lines?: InvoiceLine[];
};

export type VehicleReportRow = {
    vehicle_id: number;
    plat: string;
    trip: number;
    jumlah_barang: number;
    nilai_invoice: number;
    biaya: number;
    margin: number;
};
