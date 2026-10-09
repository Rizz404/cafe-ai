# Panduan untuk AI: membuat proyek baru dari basis Cafe AI

Dokumen ini untuk agen AI yang diminta membuat proyek baru (mis. "Bakery AI", "Klinik AI", "Salon AI") dengan menyalin cafe-ai lalu mengganti domain dan tampilannya. Baca seluruhnya sebelum menyentuh file.

Basis: repo cafe-ai (Laravel 13, PHP 8.4, SQLite, Blade + Alpine 3 + Tailwind 4 + Vite 8, PHPUnit 12, Laravel Boost). Struktur kodenya sendiri diturunkan dari `fts-corporate-ai`; cafe-ai mempertahankan perilaku publik multi-tenant (slug di URL, tamu tanpa login).

---

## 1. Aturan kerja (wajib)

1. **Tanya dulu, kerjakan kemudian.** Ajukan pertanyaan di bagian 2 sebelum mengubah apa pun. Untuk perubahan besar, buat rencana dan minta persetujuan.
2. **"Ubah tampilan" berarti layout + desain, bukan hanya warna.** Pelajaran dari cafe-ai: proyek pertama hanya mengganti ilustrasi dan warna, sehingga komposisinya identik dengan hotel-ai dan user kecewa. Pertahankan *vibe* (lihat bagian 6), ganti *komposisi*.
3. **Bandingkan secara visual.** Ambil screenshot situs acuan dan hasil Anda pada 1440×900 dan 390×844 (Chrome DevTools MCP). Jika susunan elemennya sama, belum selesai.
4. **Jangan menjalankan tes atau commit tanpa diminta.** Commit hanya bila user meminta: dipecah per scope, satu baris Conventional Commits (`type(scope): deskripsi`), tanpa body, tanpa push.
5. **Ikuti CLAUDE.md/AGENTS.md proyek** (Boost guidelines, `php artisan make:*`, `vendor/bin/pint --dirty --format agent` setelah mengubah PHP, baca `.ai/rules` bila ada).
6. **Laporkan jujur.** Sebutkan apa yang sudah diverifikasi dan apa yang belum (mis. langkah wizard yang tidak diklik).

## 2. Pertanyaan keputusan di awal

| Keputusan | Contoh pilihan | Dampak |
|---|---|---|
| Nama & slug | `bakery-ai`, "Bakery AI", nama pendek | identitas (bagian 5) |
| Domain | apa padanan *menu*, *tempat duduk*, *reservasi*, *fasilitas* | modul, migrasi, tool AI (bagian 7) |
| Peran AI | "AI Barista" → "AI Baker", "AI Receptionist", … | prompt, label, nama command |
| Layout panggung | papan bawah (cafe), panel kertas samping, dock ikon, atau yang lain | bagian 6 |
| Arah visual | palet, tipografi, terang/gelap | token CSS |
| Cakupan | semua scene atau pembuka + beranda dulu | urutan kerja |
| Bahasa | id/en/ja atau lainnya | `lang/*`, `config/cafe.php` |
| Ilustrasi | pakai SVG lama sementara atau buat baru | `public/images`, `config` scenes |
| Git & remote | repo baru tanpa riwayat cafe-ai; URL remote baru | bagian 4 |

Gunakan AskUserQuestion dengan opsi yang konkret (ASCII mockup untuk layout membantu).

## 3. Inventaris basis

**Generik — pertahankan, cukup rename:**
- Arsitektur: `app/Modules/*` (Actions, Queries, Support, Presenters, Enums, Validation), Form Request (`app/Http/Requests/{Admin,Api,Auth}`), Policy cafe-scoped (`app/Policies/CafeScopedPolicy.php`, rekaman tenant lain = 404), middleware `ResolveCafe` (slug → tenant + bahasa) dan `AssignRequestId`.
- AI: `Modules/Assistant/Gateway` (`ModelGateway`, `CompatibleChatCompletionsGateway`, `FakeModelGateway`), `Prompt/{PromptComposer,AnswerValidator}`, `Tools/{Tool,ToolRegistry,ToolExecutor,ToolContext,ToolResult}`, `Conversation/Actions/ReplyToGuest`, `Conversation/Support/ContentGuard`, `config/assistant.php`.
- Admin & UI kit: `components/{layouts,ui,navigation}`, `App\Support\Ui\{ComponentStyles,FieldState}`, `resources/css/theme.css` (token admin), `resources/js/{app.js,ui/*,shared/*}`, `scripts/check-ui-conventions.mjs` (`npm run lint:ui`).
- Panggung tamu (mekanismenya): `x-cafe-stage` shell, `stage.js` (transisi antar-scene), `narrator.js` (narasi diketik + suara), `sound.js`, `barista.js` (chat), `reservation.js` (wizard), presenter `Modules/Experience/*`.
- Tooling: `.github/workflows/ci.yml`, `deploy/*`, `tests/{Feature,Unit,Support,js}`.

**Spesifik domain cafe — ganti atau buang:**
- Model & tabel: `Cafe`, `MenuItem`, `SeatingArea`, `SeatingImage`, `TableInventory`, `CafeKnowledgeItem`, `Reservation`, `CafeUser`; migrasi `database/migrations/2026_10_08_*`; seeder per model di `database/seeders/*`; factory.
- Modul: `Menu`, `Seating`, `Reservation` (slot 2 jam, `TableAvailability`), enum (`MenuCategory`, `AreaType`, `Occasion`, …).
- Tool AI (7 handler di `Modules/Assistant/Tools/Handlers`) dan isi `PromptComposer::systemPrompt()`.
- Teks: `lang/*/stage.php`, `lang/*/reservation.php`, `lang/*/handover.php`.
- Tampilan panggung: `resources/css/stage/*.css`, `resources/views/cafe/*`, `resources/views/opening.blade.php`, `resources/views/components/{cafe-stage,stage-icon}.blade.php`, ilustrasi `public/images/*`, `config/cafe.php` (`scenes`, `avatar_image`).

## 4. Prosedur A — Salin proyek dengan riwayat git baru

Target folder sejajar, mis. `D:\kodingan\shared\<slug>`. Sumber tidak disentuh.

```powershell
$src='D:\kodingan\shared\cafe-ai'; $dst='D:\kodingan\shared\<slug>'
robocopy $src $dst /E /NFL /NDL /NJH /NP /R:1 /W:1 `
  /XD "$src\vendor" "$src\node_modules" "$src\.git" "$src\public\build" "$src\storage" `
  /XF .env .env.backup .phpunit.result.cache hot *.sqlite
robocopy "$src\storage" "$dst\storage" .gitignore /S /NFL /NDL /NJH /NJS /NP
```
(Kode keluar robocopy 1 berarti ada file tersalin, bukan gagal.)

Lalu di folder baru:
```bash
git init -b main          # riwayat baru; jangan bawa .git dan remote cafe-ai
composer setup            # install, .env, key, migrate, npm install, build
php artisan db:seed       # data demo
```
Remote baru hanya ditambahkan bila user memberi URL (`git remote add origin <url>`). Jangan push.

## 5. Prosedur B — Rename identitas

Placeholder: `<slug>` = `bakery-ai`, `<Brand>` = "Bakery AI", `<Role>` = "AI Baker", `<snake>` = `bakery`.

| Yang diganti | File |
|---|---|
| `APP_NAME="Cafe AI"` | `.env.example`, `deploy/env.production.example` |
| Paket (masih `laravel/laravel`) — beri nama sendiri | `composer.json` (+ `composer update --lock`) |
| Slug, nama, email demo `owner@cafeai.test` | `database/seeders/{CafeSeeder,UserSeeder,CafeKnowledgeItemSeeder}.php`, `README.md` |
| Path/proses `cafe-ai` | `deploy/*.example`, `.claude/launch.json` |
| Brand di tampilan ("CAFE AI", monogram "C") | `opening.blade.php`, `components/cafe-stage.blade.php`, `cafe/menu-nav.blade.php`, `cafe/seating-nav.blade.php`, komentar `resources/css/theme.css` |
| Kunci storage browser `barista_token_`, `barista_pending_topic`, `cafe_sound`, `reservation_draft_`, `chat_draft_` | `resources/js/{reservation,stage,sound,barista}.js`, `resources/views/cafe/chat.blade.php` (ganti serentak; JS dan Blade harus sama) |
| Command `barista:chat` | `app/Console/Commands/BaristaChatCommand.php`, README |
| Peran AI ("AI Barista") | `lang/*/stage.php` (`chat_heading`, label), `PromptComposer`, `ContentGuard` (balasan baku) |

Penamaan kelas `Cafe*`/`Barista*` boleh dipertahankan di tahap pertama bila domainnya mirip (tenant = toko). Bila diganti (mis. `Cafe` → `Store`), ganti juga tabel, route param `{cafeSlug}`, `ResolveCafe`, policy, factory, tes; catat sebagai satu langkah tersendiri. Setelah rename: `grep -rni "cafe\|barista" --exclude-dir={vendor,node_modules,.git,storage}` dan pastikan sisa hanya yang sengaja dipertahankan.

## 6. Prosedur C — Rombak tampilan panggung (layout + desain)

### Vibe yang dipertahankan
Scene ilustrasi layar penuh; karakter pemandu (cut-out SVG, terpotong rata di bawah); kartu/narasi yang diketik; transisi "berjalan" antar-scene dengan kalimat tur; chat AI; tiga bahasa; judul serif.

### Yang harus diganti
Komposisi (posisi karakter, sapaan, navigasi, panel, chat), palet, bentuk kartu, gaya header, pembuka. Komposisi yang sudah dipakai — jangan diulang:
- **hotel-ai:** karakter tengah, teks sambutan kiri di atas gambar, kaca gelap menu di kanan, pil chat + chip di bawah tengah.
- **cafe-ai:** karakter kiri-bawah, kartu bicara krem di atasnya, papan menu enam kartu di bawah, chat sebagai laci kanan, tombol chat di celemek, panel kertas di kanan.

Ide komposisi lain: panel kertas samping (layar terbagi), dock ikon bulat di bawah dengan balon bicara, rak/etalase horizontal, buku menu yang dibuka di tengah, papan tulis di dinding sebagai navigasi.

### Di mana mengubahnya
- **Token & warna:** `resources/css/stage/tokens.css` (satu-satunya sumber warna panggung).
- **Region:** `resources/css/stage/{shell,header,greeting,board,narrator,panels,chat,wizard,opening,responsive}.css`, diimpor berurutan dari `resources/css/presentation.css` (`narrator` sebelum `panels`; `responsive` terakhir).
- **Markup:** `components/cafe-stage.blade.php` (shell), `cafe/*.blade.php` (scene), `opening.blade.php`, `components/stage-icon.blade.php` (ikon kartu; kunci `icon` dari `StageNavigation`).
- **Posisi karakter per scene:** `config/cafe.php` → `scenes.*.anchor`/`focus`/`focusMobile`.
- Panggung dikecualikan dari `lint:ui`; admin tidak. Warna admin hanya lewat token `resources/css/theme.css`.

### Kontrak JS yang tidak boleh rusak
Ganti kelas CSS dan susunan sesuka hati, tetapi pertahankan hook ini:
- Elemen dengan `data-stage` juga membawa `data-scene` (dibaca `barista.js`); tautan antar-scene memakai `data-stage-exit` (+ opsional `data-tour-line`, `data-topic`); `[data-stage-loader]`, `[data-stage-tour]`.
- Chat: `#barista-app` dan `[data-chat-widget]` wajib ada; semua `data-*` di `cafe/chat.blade.php`; `.is-open` pada `#barista-app` satu-satunya saklar; widget tetap turunan `.stage` (selector `.stage:has(.stage-chat.is-open)`); wrapper `[data-chat-widget]`, `.stage-chat`, `.stage-panels` = `display: contents`.
- Reservasi: semua `data-wizard*`, `data-step`, `data-progress-step`, `data-area-option`; radio `time_slot` langsung diikuti span label; anak pertama `[data-wizard-next]` harus node teks.
- Narator: `[data-narrator-text]`, `[data-narrator-listen]`, `[data-narrator-skip]` wajib ada; `.narrator-rest { opacity: 0 }`; jangan taruh di dalam `display: none`.
- Info: id radio `info-tab-general|policies|faq` + `data-panel` dipakai CSS.
- `stage.js` menahan navigasi 320/560/820 ms; animasi keluar di CSS tidak boleh lebih lama.
- Teks yang diuji `tests/Feature/CafeStageTest.php` harus tetap dirender server (nama, sapaan, harga, narasi, kebijakan/FAQ di DOM).

## 7. Prosedur D — Pemetaan domain

Buat tabel delta dan sepakati dengan user sebelum menulis migrasi:

| Cafe AI | Proyek baru |
|---|---|
| Cafe (tenant, slug) | toko / klinik / cabang |
| MenuItem (kategori, harga, alergen, habis) | produk / layanan |
| SeatingArea + TableInventory (slot 2 jam) | ruang / jadwal / kapasitas |
| Reservation (permintaan, dikonfirmasi tim) | booking / janji temu / pesanan |
| CafeKnowledgeItem (kebijakan, fasilitas, FAQ) | basis pengetahuan (biasanya tetap) |
| HandoverRequest (serah ke tim) | tetap |
| 7 tool AI | daftar tool baru (lihat di bawah) |
| AI Barista, `barista:chat` | peran baru |

Prinsip: pertahankan mekanisme (gateway, daftar putih tool, validasi tool, permintaan → konfirmasi manusia, kontrak JSON chat/reservasi), ganti kosakata dan skema. Karena proyek turunan belum punya data produksi, **tulis ulang migrasi** daripada menumpuk `ALTER`.

Menambah/mengganti tool AI: satu kelas `final` di `app/Modules/Assistant/Tools/Handlers` yang mengimplementasikan `Tool` (`name`, `definition` skema OpenAI, `execute(ToolContext, array)`), daftarkan di `ToolRegistry::HANDLERS`, sesuaikan aturan di `PromptComposer`. Tenant dan percakapan selalu dari `ToolContext`, tidak pernah dari argumen model. Tool tidak boleh langsung mengonfirmasi transaksi.

## 8. Urutan kerja yang disarankan

1. Salin + git baru (bagian 4), pastikan `composer setup` dan halaman demo jalan.
2. Rename identitas (bagian 5).
3. Rencana tampilan → persetujuan → rombak per fase: fondasi + pembuka/beranda (checkpoint screenshot) → navigasi → panel → chat → wizard/narator → mobile → QA.
4. Pemetaan domain → migrasi, model, modul, tool, prompt, teks `lang/*`, seeder, factory.
5. Tes (`php artisan test --compact`, `npm run test:ui`) dan cek statis (`npm run lint:ui`, `php artisan view:cache`, `npm run build`) — jalankan bila user meminta atau saat menyerahkan hasil yang ia setujui untuk diuji.
6. Commit per scope bila diminta.

## 9. Yang sering terlewat (pelajaran nyata)

- **`public/hot` basi** membuat halaman mencoba memuat aset dari dev server yang mati; hapus bila `npm run dev` tidak berjalan.
- **Heredoc Bash panjang dan string Python dengan backslash** (`\U`, `\A` di namespace PHP) gagal di lingkungan Windows ini. Tulis file dengan tool Write, atau pakai raw string `r"..."`.
- **`sed -i` pada file CRLF** sering tidak cocok; verifikasi hasil dengan `grep` setelah setiap penggantian massal.
- **Animasi vs `translate`:** elemen yang dipusatkan dengan `translate` akan meloncat bila animasi masuk juga memakai `translate`. Pusatkan dengan `left: 0; right: 0; margin-inline: auto`.
- **Spesifisitas variabel:** `.stage:has(...) { --x }` mengalahkan `.stage { --x }` di dalam media query; timpa juga selector `:has` di `responsive.css`.
- **Urutan impor CSS:** aturan modifier (`.narrator-inline`) harus dimuat setelah aturan dasar (`.narrator`).
- **Panah `↗`** dirender sebagai emoji biru; `font-variant-emoji: text` sudah dipasang di `.stage-page`.
- **Kelas Tailwind di string JS** (`barista.js`, bubble chat) tidak diatur CSS panggung; ubah string-nya bila palet baru bentrok.
- **Ilustrasi:** karakter SVG `viewBox 0 0 800 1200`, terpotong rata di bawah — boleh diskalakan dan digeser, tapi tepi bawah harus menempel ke bawah layar atau tertutup elemen lain. Scene `1920×1080`, `object-fit: cover`.
- **Versi paket:** cek `composer show --direct` dan `package.json` sebelum memakai API; pakai `search-docs` Boost.
- **Rate limiter** chat (`AppServiceProvider`) dan nama-nama route (`cafe.*`, `barista.*`, `reservation.*`, `admin.*`) dirujuk tes dan JS; ganti serentak bila di-rename.

## 10. Verifikasi sebelum menyerahkan

- Screenshot semua scene (pembuka, beranda, daftar & detail produk, ruang, fasilitas, info, staf, reservasi, chat terbuka) di 1440×900 dan 390×844, berdampingan dengan situs acuan; komposisi harus jelas berbeda.
- Klik alur: beranda → daftar → detail → reservasi (semua langkah) → kirim; buka/tutup chat; ganti bahasa; konsol tanpa error.
- `grep` sisa identitas lama nol (kecuali yang sengaja).
- Laporkan kepada user: apa yang berubah, apa yang diverifikasi, apa yang belum, dan tawarkan commit.
