from fpdf import FPDF

class PDF(FPDF):
    def header(self):
        # Tidak menggunakan header di cover (halaman 1)
        if self.page_no() > 1:
            self.set_font('Arial', 'I', 8)
            self.cell(0, 10, 'Proposal Franchise "MASTER TUKANG" - 2026', 0, 1, 'R')
            self.ln(5)

    def footer(self):
        self.set_y(-15)
        self.set_font('Arial', 'I', 8)
        self.cell(0, 10, f'Halaman {self.page_no()}', 0, 0, 'C')

    def chapter_title(self, title):
        self.set_font('Arial', 'B', 14)
        self.set_fill_color(230, 230, 230) # Abu-abu muda
        self.cell(0, 10, title, 0, 1, 'L', 1)
        self.ln(4)

    def chapter_body(self, body):
        self.set_font('Arial', '', 11)
        self.multi_cell(0, 6, body)
        self.ln()

def create_pdf():
    pdf = PDF()
    pdf.set_auto_page_break(auto=True, margin=15)

    # --- HALAMAN 1: COVER ---
    pdf.add_page()
    pdf.set_line_width(1)
    pdf.rect(10, 10, 190, 277) # Border halaman
    
    pdf.ln(40)
    pdf.set_font('Arial', 'B', 24)
    pdf.cell(0, 10, 'PROPOSAL PENAWARAN', 0, 1, 'C')
    pdf.cell(0, 10, 'KERJASAMA (FRANCHISE)', 0, 1, 'C')
    
    pdf.ln(20)
    pdf.set_font('Arial', 'B', 40)
    pdf.set_text_color(200, 50, 0) # Warna aksen oranye bata
    pdf.cell(0, 20, 'MASTER TUKANG', 0, 1, 'C')
    
    pdf.ln(10)
    pdf.set_font('Arial', 'I', 14)
    pdf.set_text_color(0, 0, 0)
    pdf.cell(0, 10, '"Solusi Bangunan, Renovasi, & Perbaikan Terpercaya"', 0, 1, 'C')
    
    pdf.ln(30)
    pdf.set_font('Arial', 'B', 12)
    pdf.cell(0, 10, '[ TEMPAT LOGO ANDA DISINI ]', 1, 1, 'C') # Placeholder logo
    
    pdf.ln(30)
    pdf.set_font('Arial', 'B', 16)
    pdf.cell(0, 10, 'EST. 2025', 0, 1, 'C')
    
    pdf.ln(10)
    pdf.set_font('Arial', '', 12)
    pdf.cell(0, 10, 'Bangun Bisnis Jasa Konstruksi Modern di Kota Anda', 0, 1, 'C')

    # --- HALAMAN 2: TENTANG KAMI ---
    pdf.add_page()
    pdf.chapter_title('01. TENTANG KAMI')
    text_about = (
        "Halo Sahabat Master Tukang,\n\n"
        "Berawal dari keresahan masyarakat akan sulitnya mencari tenaga tukang yang profesional, jujur, "
        "dan transparan dalam harga, MASTER TUKANG hadir sebagai solusi. Sejak awal berdiri, kami memulai "
        "layanan ini dengan misi sederhana: mengubah citra 'tukang bangunan' menjadi tenaga ahli yang rapi "
        "dan terpercaya.\n\n"
        "Melalui riset dan pengembangan sistem yang matang, kami berhasil menemukan formula manajemen jasa "
        "konstruksi skala ritel yang efektif. Dengan dukungan Standard Operating Procedure (SOP) yang ketat, "
        "kami tidak hanya memperbaiki bangunan, tapi juga membangun kepercayaan pelanggan.\n\n"
        "Kini, Master Tukang siap melebarkan sayap ke seluruh Indonesia melalui sistem kemitraan (Franchise). "
        "Kami mengundang Anda untuk menjadi bagian dari revolusi industri jasa pertukangan modern ini.\n\n"
        "VISI KAMI:\n"
        "Menjadi penyedia jasa pertukangan dan konstruksi nomor 1 di Indonesia yang berbasis teknologi dan pelayanan prima."
    )
    pdf.chapter_body(text_about)

    # --- HALAMAN 3: KEUNGGULAN ---
    pdf.add_page()
    pdf.chapter_title('02. KENAPA HARUS FRANCHISE DENGAN KAMI?')
    text_adv = (
        "1. PASAR ABADI & LUAS\n"
        "Setiap bangunan pasti membutuhkan perawatan. Pasar renovasi tidak mengenal musim.\n\n"
        "2. MINIM RESIKO BAHAN BAKU\n"
        "Berbeda dengan F&B, bisnis jasa tidak memiliki resiko bahan baku basi atau kadaluarsa.\n\n"
        "3. INVESTASI TERJANGKAU\n"
        "Pilihan paket investasi yang fleksibel mulai dari skala mobile hingga kantor fisik.\n\n"
        "4. HIGH MARGIN\n"
        "Keuntungan jasa servis yang besar (Gross Margin jasa bisa mencapai 60-80%).\n\n"
        "5. SISTEM TERPUSAT\n"
        "Dukungan aplikasi/sistem admin untuk pencatatan order dan keuangan yang transparan.\n\n"
        "6. PELATIHAN & SOP\n"
        "Kami melatih tukang Anda menjadi tenaga profesional dengan standar kerja tinggi."
    )
    pdf.chapter_body(text_adv)

    # --- HALAMAN 4: LAYANAN ---
    pdf.chapter_title('03. PRODUK & LAYANAN (SERVICES)')
    text_services = (
        "Master Tukang menawarkan solusi One Stop Solution untuk segala masalah bangunan:\n\n"
        "- Master Sipil: Renovasi rumah, pengecatan dinding, perbaikan atap bocor, pemasangan keramik.\n"
        "- Master Kelistrikan (ME): Instalasi listrik baru, perbaikan korsleting, pemasangan lampu hias.\n"
        "- Master Plumbing: Perbaikan saluran air mampet, instalasi pipa air, pasang toren & pompa.\n"
        "- Master Las: Pembuatan pagar, kanopi, teralis, dan railing tangga.\n"
        "- Master Cool: Cuci AC, bongkar pasang AC, dan perbaikan pendingin ruangan."
    )
    pdf.chapter_body(text_services)

    # --- HALAMAN 5: PAKET ---
    pdf.add_page()
    pdf.chapter_title('04. PILIHAN PAKET FRANCHISE')
    
    pdf.set_font('Arial', 'B', 12)
    pdf.cell(0, 8, 'A. PAKET "MOBILE UNIT" (STARTER) - Rp 75.000.000,-', 0, 1)
    pdf.set_font('Arial', '', 11)
    pdf.multi_cell(0, 6, "Konsep: Unit reaksi cepat menggunakan motor roda tiga/motor box.\nFasilitas: Branding Kendaraan, 1 Set Alat Kerja Lengkap, Seragam, Training, Sistem Booking Online.\n")
    
    pdf.set_font('Arial', 'B', 12)
    pdf.cell(0, 8, 'B. PAKET "WORKSHOP HUB" (MEDIUM) - Rp 125.000.000,-', 0, 1)
    pdf.set_font('Arial', '', 11)
    pdf.multi_cell(0, 6, "Konsep: Ruko kecil/Posko sebagai basecamp peralatan & material.\nFasilitas: Renovasi & Branding Kantor (Neon Box), 3 Set Alat Kerja Tim, Komputer Admin, Stok Material Awal, Training Manajemen.\n")
    
    pdf.set_font('Arial', 'B', 12)
    pdf.cell(0, 8, 'C. PAKET "CONTRACTOR CENTER" (PREMIUM) - Rp 250.000.000,-', 0, 1)
    pdf.set_font('Arial', '', 11)
    pdf.multi_cell(0, 6, "Konsep: Kantor konsultan renovasi lengkap dengan display material.\nFasilitas: Interior Kantor Premium, Alat Konstruksi Berat, 5 Set Alat Kerja, Sistem Kasir Lengkap, Dukungan Arsitek Pusat.\n")

    # --- HALAMAN 6: RINCIAN TABEL ---
    pdf.add_page()
    pdf.chapter_title('05. RINCIAN FASILITAS (CONTOH PAKET 75 JUTA)')
    
    # Table Header
    pdf.set_fill_color(220, 220, 220)
    pdf.set_font('Arial', 'B', 10)
    pdf.cell(40, 10, 'KATEGORI', 1, 0, 'C', 1)
    pdf.cell(100, 10, 'ITEM FASILITAS', 1, 0, 'C', 1)
    pdf.cell(50, 10, 'ESTIMASI NILAI', 1, 1, 'C', 1)
    
    # Table Body
    pdf.set_font('Arial', '', 10)
    data_invest = [
        ("Aset Alat Kerja", "2 Bor, 2 Gerinda, Las, Toolset, Tangga, Safety Gear", "Rp 30.000.000"),
        ("Setup Outlet", "Neon Box, Meja, Kursi, Branding Dinding, Seragam", "Rp 20.000.000"),
        ("Legal & Sistem", "Franchise Fee 5 Thn, Aplikasi, SOP, Training", "Rp 15.000.000"),
        ("Stok Awal", "Kabel, Kran, Pipa, Semen, Cat, Material fast moving", "Rp 10.000.000"),
        ("TOTAL", "PAKET WORKSHOP HUB", "Rp 75.000.000")
    ]
    
    for row in data_invest:
        if row[0] == "TOTAL":
            pdf.set_font('Arial', 'B', 10)
        pdf.cell(40, 10, row[0], 1)
        pdf.cell(100, 10, row[1], 1)
        pdf.cell(50, 10, row[2], 1, 1, 'R')

    pdf.ln(10)
    
    # --- HALAMAN 7: AUTOPILOT ---
    pdf.chapter_title('06. PAKET INVESTOR (AUTOPILOT)')
    text_auto = (
        "Solusi bagi Anda yang sibuk namun ingin memiliki Passive Income dari bisnis konstruksi.\n\n"
        "Nilai Investasi: Rp 125.000.000,-\n"
        "Sistem Pengelolaan: Full Operasional dijalankan oleh Manajemen Pusat.\n"
        "Pembagian Keuntungan (Profit Sharing):\n"
        "   - 75% untuk Mitra (Investor)\n"
        "   - 25% untuk Manajemen (Fee Pengelolaan)\n\n"
        "Keuntungan: Anda tidak perlu pusing memikirkan operasional harian, cukup menerima laporan keuangan dan transfer profit setiap bulan."
    )
    pdf.chapter_body(text_auto)

    # --- HALAMAN 8: PROYEKSI ---
    pdf.add_page()
    pdf.chapter_title('07. SKENARIO KEUNTUNGAN BULANAN')
    pdf.set_font('Arial', 'I', 10)
    pdf.cell(0, 10, '(Simulasi Paket Workshop - Estimasi Kinerja Rata-rata)', 0, 1)

    # Table Header
    pdf.set_fill_color(220, 220, 220)
    pdf.set_font('Arial', 'B', 10)
    pdf.cell(70, 10, 'KOMPONEN', 1, 0, 'C', 1)
    pdf.cell(60, 10, 'SKENARIO MEDIUM', 1, 0, 'C', 1)
    pdf.cell(60, 10, 'SKENARIO TINGGI', 1, 1, 'C', 1)
    
    # Table Body
    pdf.set_font('Arial', '', 10)
    data_proj = [
        ("Order Per Hari", "6 Order", "10 Order"),
        ("Omzet Sebulan (26 Hari)", "Rp 46.800.000", "Rp 78.000.000"),
        ("", "", ""), # Empty row separator
        ("BIAYA OPERASIONAL", "", ""),
        ("Upah Tukang (Bagi Hasil)", "Rp 18.720.000", "Rp 31.200.000"),
        ("Bahan & Operasional", "Rp 3.500.000", "Rp 5.000.000"),
        ("Gaji Admin & Sewa", "Rp 5.000.000", "Rp 5.000.000"),
        ("Royalti Fee 5%", "Rp 2.340.000", "Rp 3.900.000"),
        ("", "", ""),
        ("PROFIT BERSIH", "Rp 17.240.000", "Rp 32.900.000"),
        ("Estimasi BEP", "4-5 Bulan", "2-3 Bulan"),
    ]

    for row in data_proj:
        if row[0] == "PROFIT BERSIH" or row[0] == "BIAYA OPERASIONAL":
             pdf.set_font('Arial', 'B', 10)
        else:
             pdf.set_font('Arial', '', 10)
             
        pdf.cell(70, 8, row[0], 1)
        pdf.cell(60, 8, row[1], 1, 0, 'R')
        pdf.cell(60, 8, row[2], 1, 1, 'R')

    # --- HALAMAN 9: SYARAT ALUR ---
    pdf.add_page()
    pdf.chapter_title('08. SYARAT & ALUR KEMITRAAN')
    text_syarat = (
        "SYARAT BERMITRA:\n"
        "1. Siap menjadi entrepreneur sukses dan memiliki modal yang cukup.\n"
        "2. Memiliki lokasi usaha (Ruko/Garasi) yang strategis.\n"
        "3. Bersedia mematuhi SOP dan standar harga Master Tukang.\n"
        "4. Wajib membeli branding dan peralatan utama dari Pusat.\n\n"
        "ALUR KEMITRAAN:\n"
        "1. Mengisi Formulir Kemitraan.\n"
        "2. Persetujuan Lokasi & Survey.\n"
        "3. Pembayaran Paket Kemitraan.\n"
        "4. Penandatanganan Kerjasama (MOU).\n"
        "5. Setup Outlet & Pengiriman Alat.\n"
        "6. Training Pegawai (Teknis & Admin).\n"
        "7. Grand Opening Master Tukang."
    )
    pdf.chapter_body(text_syarat)

    # --- HALAMAN 10: KONTAK ---
    pdf.add_page()
    pdf.chapter_title('KONTAK KAMI')
    
    pdf.set_font('Arial', 'B', 16)
    pdf.cell(0, 10, 'SIAP MENJADI JURAGAN KONSTRUKSI?', 0, 1, 'C')
    
    pdf.set_font('Arial', '', 12)
    pdf.multi_cell(0, 8, "Jangan lewatkan kesempatan menguasai pasar jasa perbaikan rumah di kota Anda. Hubungi kami sekarang untuk konsultasi lokasi.\n\n"
                   "Alamat Kantor Pusat: [Jalan Catur B 05 Mojoroto Kota Kediri]\n"
                   "Hotline Kemitraan: [081331478423]\n"
                   "Instagram: @mastertukang.id\n"
                   "Email: franchise@mastertukang.com\n", 0, 'C')

    # --- HALAMAN 11: LAMPIRAN ---
    pdf.add_page()
    pdf.chapter_title('LAMPIRAN: FORMULIR')
    
    pdf.set_font('Arial', '', 12)
    pdf.cell(0, 10, 'FORMULIR PENDAFTARAN MITRA', 0, 1)
    pdf.cell(0, 10, 'Nama: _______________________________________', 0, 1)
    pdf.cell(0, 10, 'No. Telp/WA: __________________________________', 0, 1)
    pdf.cell(0, 10, 'Rencana Lokasi: ______________________________', 0, 1)
    pdf.cell(0, 10, 'Pilihan Paket: [ ] 75 Juta [ ] 125 Juta [ ] 250 Juta [ ] Autopilot', 0, 1)
    
    pdf.ln(10)
    pdf.cell(0, 10, 'RINGKASAN PERJANJIAN (MOU)', 0, 1, 'B')
    pdf.multi_cell(0, 8, "- Lisensi: Hak penggunaan merek selama 5 tahun.\n"
                   "- Royalti: Wajib membayar Royalti Fee 5% dari omzet kotor setiap bulan.\n"
                   "- Radius: Mitra mendapatkan proteksi wilayah usaha radius 3-5 KM.\n"
                   "- Kewajiban: Mitra wajib menjaga nama baik brand dan mengikuti SOP Pusat.")
    
    pdf.ln(20)
    pdf.cell(0, 10, '(Tanda Tangan Pemohon)', 0, 1, 'R')

    file_path = "Proposal_Franchise_Master_Tukang_2026.pdf"
    pdf.output(file_path)
    return file_path

# Execute
pdf_path = create_pdf()
print(f"File created at: {pdf_path}")
