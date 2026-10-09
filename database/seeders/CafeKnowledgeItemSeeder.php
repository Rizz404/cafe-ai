<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\CafeKnowledgeItem;
use Illuminate\Database\Seeder;

class CafeKnowledgeItemSeeder extends Seeder
{
    public function run(): void
    {
        $cafe = Cafe::where('name', CafeSeeder::NAME)->firstOrFail();
        $sort = 0;

        foreach ($this->knowledgeDefinitions() as $definition) {
            $cafe->knowledgeItems()->updateOrCreate(
                ['title' => $definition['title']],
                [...$definition, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function knowledgeDefinitions(): array
    {
        return [
            [
                'category' => CafeKnowledgeItem::CATEGORY_GENERAL, 'title' => 'Tentang Cafe AI', 'tags' => ['about', 'cerita', 'story'], 'image_url' => null,
                'body' => 'Cafe AI adalah kafe di Kemang yang menyajikan kopi single origin Nusantara, dapur rumahan, dan roti yang dipanggang setiap pagi. Kami buka setiap hari pukul 08.00 sampai 22.00.',
                'translations' => [
                    'en' => ['title' => 'About Cafe AI', 'body' => 'Cafe AI is a cafe in Kemang serving single-origin Indonesian coffee, a homestyle kitchen and bread baked every morning. We are open every day from 08:00 to 22:00.'],
                    'ja' => ['title' => 'Cafe AIについて', 'body' => 'Cafe AIはクマンにあるカフェで、インドネシア産シングルオリジンのコーヒー、家庭的なキッチン、毎朝焼くパンをご用意しています。毎日8:00〜22:00に営業しています。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Kebijakan reservasi meja', 'tags' => ['reservasi', 'reservation', 'batal', 'cancel'], 'image_url' => null,
                'body' => 'Meja ditahan selama 15 menit dari awal sesi. Pembatalan gratis sampai 2 jam sebelum sesi dimulai. Setiap sesi berlangsung 2 jam; bila kafe tidak penuh, Anda boleh tinggal lebih lama. Biaya reservasi hanya berlaku untuk Ruang Meeting Privat dan dihitung sebagai bagian dari minimum belanja.',
                'translations' => [
                    'en' => ['title' => 'Table reservation policy', 'body' => 'Tables are held for 15 minutes from the start of the slot. Cancellation is free up to 2 hours before the slot begins. Each slot is 2 hours; if the cafe is not full you are welcome to stay longer. The reservation fee applies to the Private Meeting Room only and counts towards its minimum spend.'],
                    'ja' => ['title' => 'テーブル予約について', 'body' => 'お席は枠の開始から15分間お取り置きします。枠の開始2時間前までは無料でキャンセルできます。1枠は2時間ですが、混雑していなければ延長も可能です。予約料はプライベートルームのみで、最低ご利用金額に充当されます。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Alergi dan kebutuhan diet', 'tags' => ['alergi', 'allergy', 'allergen', 'vegan', 'halal', 'diet'], 'image_url' => null,
                'body' => 'Semua menu kami halal. Dapur kami menangani susu, gluten, telur, kacang, kedelai, dan makanan laut, sehingga kami tidak dapat menjamin bebas silang kontaminasi. Informasi alergen di menu hanya panduan; mohon beri tahu barista bila Anda memiliki alergi serius.',
                'translations' => [
                    'en' => ['title' => 'Allergies and dietary needs', 'body' => 'Everything on our menu is halal. Our kitchen handles milk, gluten, egg, nuts, soy and seafood, so we cannot guarantee freedom from cross-contact. Allergen details on the menu are a guide only; please tell your barista about any serious allergy.'],
                    'ja' => ['title' => 'アレルギーと食事制限', 'body' => 'メニューはすべてハラールです。厨房では乳、小麦、卵、ナッツ、大豆、魚介を扱っているため、コンタミネーションを完全に防ぐことはできません。メニューのアレルゲン情報は目安です。重いアレルギーがある場合は必ずスタッフにお伝えください。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Metode pembayaran', 'tags' => ['bayar', 'payment', 'qris', 'kartu', 'cash'], 'image_url' => null,
                'body' => 'Kami menerima tunai, QRIS, kartu debit, dan kartu kredit Visa serta Mastercard. Reservasi lewat situs ini tidak memerlukan pembayaran di muka.',
                'translations' => [
                    'en' => ['title' => 'Payment methods', 'body' => 'We accept cash, QRIS, debit cards, and Visa and Mastercard credit cards. Reserving through this site does not require payment upfront.'],
                    'ja' => ['title' => 'お支払い方法', 'body' => '現金、QRIS、デビットカード、VisaとMastercardのクレジットカードをご利用いただけます。このサイトからのご予約に前払いは不要です。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Wi-Fi gratis dan colokan listrik', 'tags' => ['wifi', 'internet', 'colokan', 'power', 'outlet'], 'image_url' => 'images/scenes/seating.svg',
                'body' => 'Wi-Fi cepat gratis untuk semua tamu; kata sandinya ada di struk atau tanyakan kepada barista. Colokan tersedia di Indoor Lounge, Bar Counter, dan Ruang Meeting Privat.',
                'translations' => [
                    'en' => ['title' => 'Free Wi-Fi and power outlets', 'body' => 'Fast Wi-Fi is free for all guests; the password is on your receipt or just ask a barista. Power outlets are available in the Indoor Lounge, the Bar Counter and the Private Meeting Room.'],
                    'ja' => ['title' => '無料Wi-Fiと電源', 'body' => '高速Wi-Fiはすべてのお客様が無料でご利用いただけます。パスワードはレシートに記載、またはスタッフにお尋ねください。電源はインドアラウンジ、カウンター席、プライベートルームにあります。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Ramah untuk bekerja', 'tags' => ['kerja', 'work', 'laptop', 'coworking', 'meeting'], 'image_url' => 'images/scenes/counter.svg',
                'body' => 'Laptop diperbolehkan setiap hari. Pada akhir pekan pukul 17.00 sampai 21.00, meja di Indoor Lounge diprioritaskan untuk tamu yang makan dan minum.',
                'translations' => [
                    'en' => ['title' => 'Work-friendly', 'body' => 'Laptops are welcome every day. On weekends from 17:00 to 21:00, Indoor Lounge tables are kept for guests who are dining.'],
                    'ja' => ['title' => '作業にもおすすめ', 'body' => 'ノートパソコンは毎日ご利用いただけます。週末の17:00〜21:00は、インドアラウンジのテーブルをお食事のお客様優先とさせていただきます。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Musik akustik live', 'tags' => ['musik', 'music', 'live', 'akustik', 'acoustic'], 'image_url' => 'images/scenes/terrace.svg',
                'body' => 'Penampilan musik akustik live setiap Jumat dan Sabtu pukul 19.30 sampai 21.30 di Garden Terrace. Tidak ada biaya tambahan.',
                'translations' => [
                    'en' => ['title' => 'Live acoustic music', 'body' => 'Live acoustic music every Friday and Saturday from 19:30 to 21:30 on the Garden Terrace. There is no cover charge.'],
                    'ja' => ['title' => 'ライブアコースティック', 'body' => '毎週金曜と土曜の19:30〜21:30、ガーデンテラスでアコースティックライブを開催します。入場料はかかりません。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Teras ramah hewan peliharaan', 'tags' => ['hewan', 'pet', 'anjing', 'dog', 'kucing', 'cat'], 'image_url' => 'images/seating/garden-terrace.svg',
                'body' => 'Anjing dan kucing yang jinak boleh duduk bersama Anda di Garden Terrace dengan tali atau di dalam tas. Kami menyediakan mangkuk air.',
                'translations' => [
                    'en' => ['title' => 'Pet-friendly terrace', 'body' => 'Well-behaved dogs and cats are welcome on the Garden Terrace, on a leash or in a carrier. We provide water bowls.'],
                    'ja' => ['title' => 'ペット同伴OKのテラス', 'body' => 'おとなしいワンちゃん・猫ちゃんは、リードまたはキャリーに入れてガーデンテラスにご同伴いただけます。お水もご用意しています。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_EVENTS, 'title' => 'Acara privat dan katering', 'tags' => ['acara', 'event', 'private', 'catering', 'arisan', 'ulang tahun', 'birthday'], 'image_url' => 'images/seating/private-room.svg',
                'body' => 'Ruang Meeting Privat dapat dipakai untuk 6 sampai 12 orang. Kami juga menerima pesanan katering dan kue ulang tahun minimal 2 hari sebelumnya. Untuk acara khusus, hubungi tim kami agar kami siapkan penawarannya.',
                'translations' => [
                    'en' => ['title' => 'Private events and catering', 'body' => 'The Private Meeting Room fits 6 to 12 people. We also take catering and birthday cake orders at least 2 days ahead. For special events, contact our team and we will prepare a quote.'],
                    'ja' => ['title' => '貸切イベントとケータリング', 'body' => 'プライベートルームは6〜12名様でご利用いただけます。ケータリングやバースデーケーキは2日前までにご注文ください。特別なイベントはスタッフにご相談いただければお見積りいたします。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_EVENTS, 'title' => 'Workshop kopi dan cupping', 'tags' => ['workshop', 'cupping', 'kopi', 'coffee', 'kelas'], 'image_url' => 'images/scenes/backbar.svg',
                'body' => 'Setiap Minggu pagi pukul 10.00 kami mengadakan sesi cupping dan workshop seduh manual untuk maksimal 8 peserta. Pendaftaran lewat tim kami.',
                'translations' => [
                    'en' => ['title' => 'Coffee workshops and cupping', 'body' => 'Every Sunday at 10:00 we hold a cupping session and manual brewing workshop for up to 8 people. Sign up through our team.'],
                    'ja' => ['title' => 'コーヒーワークショップとカッピング', 'body' => '毎週日曜の10:00に、最大8名様でのカッピングとハンドドリップのワークショップを開催します。お申し込みはスタッフまで。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_LOCATION, 'title' => 'Lokasi dan parkir', 'tags' => ['lokasi', 'location', 'parkir', 'parking', 'alamat', 'address', 'motor', 'mobil'], 'image_url' => 'images/scenes/window.svg',
                'body' => 'Kami berada di Jl. Kemang Raya No. 21, Jakarta Selatan, sekitar 5 menit berjalan kaki dari halte Kemang. Parkir mobil terbatas untuk sekitar 10 mobil; parkir motor tersedia di depan kafe.',
                'translations' => [
                    'en' => ['title' => 'Location and parking', 'body' => 'We are at Jl. Kemang Raya No. 21, South Jakarta, about a 5-minute walk from the Kemang bus stop. Car parking is limited to around 10 cars; motorbike parking is in front of the cafe.'],
                    'ja' => ['title' => 'アクセスと駐車場', 'body' => '所在地は南ジャカルタのJl. Kemang Raya No. 21で、クマンのバス停から徒歩約5分です。駐車場は車約10台分に限られ、バイクは店の前に停められます。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_LOCATION, 'title' => 'Pesan antar dan bawa pulang', 'tags' => ['delivery', 'antar', 'takeaway', 'bawa pulang', 'gofood', 'grabfood'], 'image_url' => null,
                'body' => 'Semua menu bisa dibawa pulang. Pesan antar tersedia lewat GoFood dan GrabFood dengan jam yang sama dengan jam buka kafe.',
                'translations' => [
                    'en' => ['title' => 'Delivery and takeaway', 'body' => 'Everything on the menu is available to take away. Delivery is available through GoFood and GrabFood during the cafe\'s opening hours.'],
                    'ja' => ['title' => 'デリバリーとテイクアウト', 'body' => 'すべてのメニューをテイクアウトできます。デリバリーはGoFoodとGrabFoodで、営業時間内にご利用いただけます。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FAQ, 'title' => 'Apakah boleh datang tanpa reservasi?', 'tags' => ['walk-in', 'tanpa reservasi', 'langsung datang'], 'image_url' => null,
                'body' => 'Boleh. Sebagian besar meja kami diperuntukkan bagi tamu yang datang langsung. Reservasi disarankan untuk akhir pekan, rombongan di atas 4 orang, dan Ruang Meeting Privat.',
                'translations' => [
                    'en' => ['title' => 'Do you take walk-ins?', 'body' => 'Yes. Most of our tables are kept for walk-in guests. Reservations are recommended for weekends, groups of more than 4, and the Private Meeting Room.'],
                    'ja' => ['title' => '予約なしでも入れますか？', 'body' => 'はい。ほとんどのお席は予約なしのお客様用です。週末、5名様以上、プライベートルームはご予約をおすすめします。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FAQ, 'title' => 'Bolehkah membawa kue dari luar?', 'tags' => ['kue', 'cake', 'bawa', 'outside food', 'corkage'], 'image_url' => null,
                'body' => 'Kue ulang tahun dari luar boleh dibawa dengan biaya potong Rp 25.000 per kue. Makanan dan minuman lain dari luar tidak diperkenankan.',
                'translations' => [
                    'en' => ['title' => 'Can I bring a cake from outside?', 'body' => 'Outside birthday cakes are welcome for a cake-cutting fee of Rp 25,000 per cake. Other outside food and drinks are not allowed.'],
                    'ja' => ['title' => 'ケーキの持ち込みはできますか？', 'body' => 'バースデーケーキの持ち込みは、1台につきカット代Rp 25,000で承ります。その他の食べ物・飲み物の持ち込みはご遠慮ください。'],
                ],
            ],
            [
                'category' => CafeKnowledgeItem::CATEGORY_FAQ, 'title' => 'Apakah ada musala?', 'tags' => ['musala', 'mushola', 'ibadah', 'prayer', 'sholat'], 'image_url' => null,
                'body' => 'Ada musala kecil di sebelah toilet, lengkap dengan perlengkapan salat. Terbuka selama jam buka kafe.',
                'translations' => [
                    'en' => ['title' => 'Is there a prayer room?', 'body' => 'There is a small prayer room next to the restrooms, with prayer mats and garments. It is open during the cafe\'s opening hours.'],
                    'ja' => ['title' => '礼拝室はありますか？', 'body' => 'トイレの隣に小さな礼拝室があり、礼拝用の敷物と衣類をご用意しています。営業時間中はいつでもご利用いただけます。'],
                ],
            ],
        ];
    }
}
