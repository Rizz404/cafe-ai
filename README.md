# Cafe AI

Situs kafe interaktif dengan **AI Barista**. Tamu menjelajahi kafe seperti berdiri di depan meja kasir (menu, tempat duduk, fasilitas, informasi, reservasi meja) sambil mengobrol dengan barista AI. Barista menjawab dari data kafe yang terkontrol, mencari menu sesuai selera atau kebutuhan diet, mengecek ketersediaan meja, membuat permintaan reservasi, dan menyerahkan percakapan ke tim bila perlu. Staf mengelola semuanya lewat panel admin.

Satu aplikasi bisa melayani beberapa kafe. Setiap kafe punya halaman sendiri di `/{cafeSlug}`.

## Fitur

### Sisi tamu
- **Halaman bertema "stage"**: meja kasir, menu, tempat duduk, fasilitas, info kafe, tim, dan reservasi. Latarnya ilustrasi SVG dengan karakter barista, lengkap dengan narrator dan efek suara (tombol bisa dimatikan).
- **Tiga bahasa**: Indonesia (`id`), Inggris (`en`), dan Jepang (`ja`), dipilih lewat parameter `?lang=`.
- **Menu**: daftar per kategori (kopi, non-kopi, teh, makanan, camilan, dessert) dengan harga, tag diet, alergen, dan penanda "habis".
- **Tempat duduk**: area indoor, outdoor, bar, dan ruang privat, lengkap dengan kapasitas, fasilitas, dan biaya reservasi.
- **Chat dengan AI Barista**
  - Tahu di halaman mana tamu berada (menu atau area duduk yang sedang dilihat, form reservasi yang sedang diisi), jadi bisa menjawab "menu ini".
  - Menjawab dalam bahasa yang ditulis tamu.
  - Menampilkan kartu menu, area duduk, dan ketersediaan langsung di dalam chat.
- **Reservasi meja**: pilih tanggal dan sesi 2 jam, jumlah tamu, area duduk, lalu kirim permintaan reservasi (status awal `pending`). Tidak ada pembayaran di muka.

### Sisi admin (`/admin`)
- Dashboard ringkasan.
- CRUD **menu** (harga, kategori, tag, alergen, tandai habis).
- CRUD **area duduk** (kapasitas, biaya reservasi, minimum belanja, gambar).
- CRUD **knowledge items**, yaitu basis pengetahuan yang menjadi sumber jawaban barista (kebijakan, fasilitas, acara, lokasi, FAQ).
- Daftar **reservasi** dan ubah statusnya (`pending`, `confirmed`, `cancelled`). Membatalkan reservasi mengembalikan mejanya ke inventori.
- **Handover**: percakapan yang diserahkan barista ke tim. Staf bisa membalas langsung ke tamu dan menandainya selesai.
- Peran pengguna per kafe: `owner` dan `staff`.

## Cara kerja AI Barista

Kode ada di [app/Modules/Assistant/](app/Modules/Assistant) (model, prompt, tool) dan [app/Modules/Conversation/](app/Modules/Conversation) (satu giliran percakapan).

- **[ReplyToGuest](app/Modules/Conversation/Actions/ReplyToGuest.php)** menjalankan satu giliran percakapan dan menyimpan riwayatnya di database. Ia memanggil model lewat [ModelGateway](app/Modules/Assistant/Gateway/ModelGateway.php), yaitu endpoint chat yang kompatibel dengan OpenAI (LM Studio atau Ollama yang di-host sendiri), lalu menjalankan loop tool-calling (maksimal 6 putaran per giliran). Request dikirim dengan `reasoning_effort=none` agar model tidak melakukan fase berpikir panjang. Seluruh giliran dibatasi 85 detik supaya muat dalam batas 100 detik Cloudflare. Semua anggaran ini ada di [config/assistant.php](config/assistant.php). Prompt disusun oleh [PromptComposer](app/Modules/Assistant/Prompt/PromptComposer.php).
- **[ToolRegistry](app/Modules/Assistant/Tools/ToolRegistry.php)** adalah daftar putih alat yang bisa dipanggil model; satu alat satu kelas di [Handlers/](app/Modules/Assistant/Tools/Handlers). Semua fakta kafe, menu, harga, dan ketersediaan meja harus lewat sini, karena model sendiri tidak dipercaya menyimpan data apa pun.

  | Tool                         | Fungsi                                                                   |
  | ---------------------------- | ------------------------------------------------------------------------ |
  | `search_knowledge`           | Mencari di knowledge base kafe                                           |
  | `search_menu`                | Mencari menu menurut kata kunci, kategori, kebutuhan diet, atau anggaran |
  | `get_menu_item`              | Detail satu menu: harga, alergen, status habis                           |
  | `search_seating`             | Mencari area duduk yang cocok dengan tanggal, jumlah tamu, dan sesi      |
  | `check_table_availability`   | Cek ketersediaan meja dan biaya reservasi untuk satu sesi                |
  | `create_reservation_request` | Membuat permintaan reservasi meja                                        |
  | `request_human_handover`     | Menyerahkan percakapan ke tim                                            |

- **[ContentGuard](app/Modules/Conversation/Support/ContentGuard.php)** adalah pengaman deterministik di kode. Pesan yang kasar, bersifat seksual, atau ilegal (Indonesia, Inggris, Jepang) dijawab dengan balasan baku tanpa sampai ke model. Balasan model yang masih memuat kata-kata tersebut juga diganti. Pola dibuat sempit supaya pertanyaan kafe yang wajar tidak ikut terblokir.
- **Harga tanpa sumber ditolak**: balasan yang memuat angka harga atau placeholder yang tidak berasal dari tool akan ditantang, supaya model tidak mengarang harga.
- **Alergi**: barista hanya mengulang alergen dari data menu, tidak pernah menyatakan sebuah hidangan "aman", dan mengarahkan tamu dengan alergi serius ke tim.
- **Handover**: alasan yang didukung adalah `special_request`, `complaint`, `group_reservation`, `private_event`, `custom_order`, `payment_issue`, dan `low_confidence`.

## Teknologi

- PHP 8.3+ (dikembangkan di 8.4), Laravel 13
- SQLite sebagai database bawaan. Session, cache, dan queue memakai driver `database`
- Vite 8 dan Tailwind CSS 4 untuk frontend, dengan JavaScript vanilla (tanpa framework) di `resources/js/`
- Font Fraunces dan DM Sans (Bunny Fonts), ilustrasi SVG di `public/images/`
- LLM lokal lewat HTTP (endpoint kompatibel OpenAI)
- PHPUnit 12 untuk tes, Laravel Pint untuk format kode

## Memulai

### Prasyarat
PHP 8.3+ dengan ekstensi SQLite, Composer, dan Node.js 20.19+ (atau 22.12+) dengan npm. Untuk fitur chat AI, Anda juga perlu server LLM yang kompatibel dengan OpenAI (lihat bagian berikutnya).

### Instalasi

```bash
composer setup
```

Perintah itu menjalankan `composer install`, menyalin `.env.example` ke `.env`, membuat `APP_KEY`, menjalankan migrasi, lalu `npm install` dan `npm run build`.

Isi data demo (hanya jalan di environment `local` dan `testing`):

```bash
php artisan db:seed
```

Seeder membuat kafe demo **Cafe AI** (slug `cafe-ai`) dengan 16 menu, 4 area duduk, inventori meja untuk 60 hari ke depan, dan knowledge items dalam tiga bahasa.

### Menjalankan

```bash
composer dev
```

Perintah ini menjalankan semua proses development lewat `php artisan dev`. Daftar prosesnya bisa dilihat dengan `php artisan dev:list`.

| Halaman         | URL                             |
| --------------- | ------------------------------- |
| Halaman pembuka | `http://localhost:8000/`        |
| Kafe demo       | `http://localhost:8000/cafe-ai` |
| Admin           | `http://localhost:8000/admin`   |

Akun admin demo (hanya untuk lokal, jangan dipakai di produksi):

```
email    : owner@cafeai.test
password : password
```

### Konfigurasi LLM

Atur di `.env`:

```dotenv
LOCAL_LLM_BASE_URL=   # mis. http://127.0.0.1:1234 (LM Studio) atau http://127.0.0.1:11434 (Ollama)
LOCAL_LLM_API_KEY=    # kosongkan jika server tidak memakai autentikasi
LOCAL_LLM_MODEL=      # nama model yang dimuat di server
```

Model harus mendukung function calling. Tanpa konfigurasi ini, halaman kafe tetap jalan tetapi chat barista tidak bisa menjawab.

Kalau perlu, ubah juga `APP_NAME`, `APP_URL`, dan `APP_TIMEZONE` (bawaan `Asia/Jakarta`).

### Mencoba barista dari terminal

```bash
php artisan barista:chat cafe-ai --locale=id
```

Pilihan `--locale` adalah `id`, `en`, atau `ja`. Cara ini berguna untuk menguji loop tool-calling tanpa membuka browser.

## Endpoint utama

| Method | Path                                         | Keterangan                   |
| ------ | -------------------------------------------- | ---------------------------- |
| GET    | `/{cafeSlug}`                                | Meja kasir kafe              |
| GET    | `/{cafeSlug}/menu`, `/menu/{itemSlug}`       | Daftar dan detail menu       |
| GET    | `/{cafeSlug}/seating`, `/seating/{areaSlug}` | Daftar dan detail area duduk |
| GET    | `/{cafeSlug}/facilities`, `/facilities/{id}` | Fasilitas dan layanan        |
| GET    | `/{cafeSlug}/info`, `/staff`, `/reservation` | Info, tim, reservasi         |
| POST   | `/{cafeSlug}/reservation/quote`              | Cek ketersediaan dan biaya   |
| POST   | `/{cafeSlug}/reservation`                    | Kirim permintaan reservasi   |
| POST   | `/{cafeSlug}/barista/start`                  | Mulai percakapan             |
| POST   | `/{cafeSlug}/barista/message`                | Kirim pesan ke barista       |
| GET    | `/{cafeSlug}/barista/history`                | Riwayat percakapan           |

Endpoint barista dan reservasi dibatasi laju (rate limit). Pembatas barista didefinisikan di [AppServiceProvider](app/Providers/AppServiceProvider.php), dan login admin dibatasi 5 percobaan per menit.

## Struktur proyek

```
app/
  Console/Commands/     barista:chat
  Http/
    Controllers/        halaman tamu, Api/ (chat dan reservasi JSON), Admin/, Auth/
    Middleware/         ResolveCafe (slug ke kafe dan bahasa), AssignRequestId
    Requests/           Form Request: Admin/, Api/, Auth/
  Models/               Cafe, MenuItem, SeatingArea, TableInventory, Reservation, Conversation, HandoverRequest, ...
  Modules/              satu folder per konteks: Actions/, Queries/, Support/, Enums/, Presenters/
    Assistant/          Gateway/ (model), Prompt/, Tools/ (daftar putih alat + Handlers/)
    Conversation/       balasan tamu, handover, ContentGuard
    Experience/         data panggung tamu (teks, narasi, latar)
    Menu/ Seating/ Knowledge/ Reservation/ Operations/
  Policies/             satu kafe hanya mengelola datanya sendiri
  Support/              SearchText, Text, Ui/ (kelas gaya komponen)
config/                 cafe.php (bahasa, scene), assistant.php (anggaran AI)
database/
  factories/            satu factory per model
  migrations/           skema (kafe, menu, tempat duduk, knowledge, percakapan, reservasi)
  seeders/              satu seeder per model, dipanggil berurutan oleh DatabaseSeeder
deploy/                 contoh nginx, PHP, supervisor, dan env produksi
lang/{id,en,ja}/        semua teks panggung tamu, validasi reservasi, dan pesan handover
public/images/          scenes/, character/, barista/, menu/, seating/ (ilustrasi SVG)
resources/
  css/                  theme.css (satu-satunya sumber token), base.css, presentation.css (panggung)
  js/                   app.js (satu Alpine.start), stage, narrator, barista, reservation, sound, shared/, ui/
  views/                halaman tamu (cafe/), admin (admin/), components/{layouts,ui,navigation}
scripts/                check-ui-conventions.mjs (npm run lint:ui)
tests/
  Feature/, Unit/, Support/, js/
```

## Mengganti ilustrasi

Semua gambar adalah SVG di `public/images/`. Latar scene ada di `scenes/`, karakter barista (PNG/WebP/SVG transparan) di `character/`, avatar bulat di `barista/avatar.png`. Gambar menu dan area duduk diacu dari kolom `image_url` (path di bawah `public/` atau URL penuh), jadi foto asli bisa dipakai langsung dari panel admin. Pemetaan scene ke gambar ada di [config/cafe.php](config/cafe.php) (`scenes`).

## Pengujian

```bash
composer test
```

Atau jalankan satu berkas:

```bash
php artisan test --compact tests/Feature/BaristaChatTest.php
```

Cakupan tes: akses admin, chat barista (termasuk giliran dengan `FakeModelGateway`), daftar putih tool, tool menu dan reservasi, permintaan reservasi, `ResolveCafe`, halaman stage, dan `ContentGuard`.

Sisi frontend punya dua pemeriksaan Node tanpa dependensi tambahan:

```bash
npm run lint:ui   # warna admin dan UI kit hanya boleh lewat token di theme.css
npm run test:ui   # klien API dan aturan lint
```

## Gaya kode

```bash
vendor/bin/pint --dirty
```

## Deployment

- Jalankan `npm run build` dan `php artisan migrate --force`.
- Set `APP_ENV=production` dan `APP_DEBUG=false`.
- Seeder demo tidak jalan di produksi. Buat akun owner dan kafe Anda sendiri.
- Pastikan server aplikasi bisa menjangkau `LOCAL_LLM_BASE_URL`. Pada setup saat ini endpoint LLM diakses lewat Tailscale.
- Karena ada batas 100 detik dari Cloudflare, jangan menaikkan batas waktu respons barista melebihi 85 detik.

## Lisensi

Project ini dibangun di atas [Laravel](https://laravel.com), yang berlisensi [MIT](https://opensource.org/licenses/MIT).
