<?php

namespace Database\Seeders;

use App\Models\CategoriPost;
use App\Models\Post;
use App\Models\Status;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUser = User::where('email', 'user@example.com')->first();

        $categories = CategoriPost::all();
        $statuses = Status::all();
        $tags = Tag::all();

        if ($categories->isEmpty() || $statuses->isEmpty() || $tags->isEmpty()) {
            return;
        }

        $contents = $this->getHtmlContents();

        for ($i = 1; $i <= 100; $i++) {
            $title = 'Post Title '.$i;
            $content = $contents[array_rand($contents)];

            Post::create([
                'uuid' => (string) Str::uuid(),
                'title' => $title,
                'slug' => Str::slug($title).'-'.$i,
                'excerpt' => strip_tags(substr($content, 0, 150)).'...',
                'content' => $content,
                'cover_image' => 'https://api.dicebear.com/7.x/shapes/svg?seed='.Str::uuid(),
                'author_id' => $defaultUser?->id,
                'category_id' => $categories->random()->id,
                'status_id' => $statuses->random()->id,
                'tag_id' => $tags->random()->id,
                'published_at' => rand(0, 1) ? now()->subDays(rand(0, 365)) : null,
                'views' => (string) rand(0, 10000),
                'meta_description' => strip_tags(substr($content, 0, 160)),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function getHtmlContents(): array
    {
        return [
            // Article with headings, paragraphs, and lists
            '<h2>Mengenal Dasar-Dasar Web Development</h2>
<p>Web development adalah proses membangun dan memelihara website. Dalam dunia modern saat ini, kemampuan web development menjadi salah satu skill yang sangat dibutuhkan.</p>
<h3>Apa itu HTML?</h3>
<p><strong>HTML (HyperText Markup Language)</strong> adalah bahasa markup standar untuk membuat halaman web. HTML mendeskripsikan struktur halaman web menggunakan elemen-elemen seperti:</p>
<ul>
<li><strong>Headings</strong> - Judul dan subjudul (<code>&lt;h1&gt;</code> sampai <code>&lt;h6&gt;</code>)</li>
<li><strong>Paragraphs</strong> - Paragraf teks (<code>&lt;p&gt;</code>)</li>
<li><strong>Links</strong> - Tautan ke halaman lain (<code>&lt;a&gt;</code>)</li>
<li><strong>Images</strong> - Gambar (<code>&lt;img&gt;</code>)</li>
<li><strong>Lists</strong> - Daftar item (<code>&lt;ul&gt;</code>, <code>&lt;ol&gt;</code>, <code>&lt;li&gt;</code>)</li>
</ul>
<h3>Apa itu CSS?</h3>
<p><em>CSS (Cascading Style Sheets)</em> digunakan untuk mengatur tampilan dan layout halaman web. Dengan CSS, kita bisa mengatur warna, font, spacing, dan masih banyak lagi.</p>
<h3>Apa itu JavaScript?</h3>
<p>JavaScript adalah bahasa pemrograman yang membuat halaman web menjadi <strong>interaktif</strong>. Dengan JavaScript, Anda bisa membuat animasi, meng-handle form submission, dan berkomunikasi dengan server.</p>
<blockquote><p>"The web is not just a collection of pages, it is a platform for building applications." — Tim Berners-Lee</p></blockquote>
<p>Memahami ketiga fundamental ini adalah langkah pertama untuk menjadi seorang web developer yang handal.</p>',

            // Technical tutorial with code blocks
            '<h2>Memulai dengan Laravel: Panduan Lengkap untuk Pemula</h2>
<p>Laravel adalah framework PHP yang sangat populer untuk membangun aplikasi web. Dalam tutorial ini, kita akan belajar cara memulai proyek Laravel dari nol.</p>
<h3>Langkah 1: Instalasi Laravel</h3>
<p>Pertama-tama, pastikan Anda sudah menginstal <strong>Composer</strong> di komputer Anda. Jalankan perintah berikut di terminal:</p>
<pre><code>composer create-project laravel/laravel my-project
cd my-project
php artisan serve</code></pre>
<p>Setelah itu, buka browser dan akses <code>http://localhost:8000</code> untuk melihat halaman default Laravel.</p>
<h3>Langkah 2: Membuat Model dan Migration</h3>
<p>Di Laravel, kita menggunakan <strong>Eloquent ORM</strong> untuk berinteraksi dengan database. Berikut cara membuat model:</p>
<pre><code>php artisan make:model Post -m</code></pre>
<p>Perintah ini akan membuat model <code>Post</code> beserta migration-nya. Buka file migration yang sudah dibuat dan tambahkan kolom-kolom yang dibutuhkan:</p>
<pre><code>Schema::create(&#39;posts&#39;, function (Blueprint $table) {
    $table-&gt;id();
    $table-&gt;string(&#39;title&#39;);
    $table-&gt;text(&#39;content&#39;);
    $table-&gt;foreignId(&#39;user_id&#39;)-&gt;constrained();
    $table-&gt;timestamps();
});</code></pre>
<h3>Langkah 3: Membuat Controller</h3>
<p>Controller berfungsi sebagai penghubung antara Model dan View. Berikut cara membuatnya:</p>
<pre><code>php artisan make:controller PostController --resource</code></pre>
<p>Controller resource akan otomatis membuat metode-metode CRUD seperti <code>index()</code>, <code>store()</code>, <code>show()</code>, <code>update()</code>, dan <code>destroy()</code>.</p>
<ol>
<li>Buka file <code>PostController.php</code></li>
<li>Tambahkan logika untuk setiap metode</li>
<li>Hubungkan dengan Model dan View</li>
</ol>
<p>Selamat! Anda sudah berhasil membuat aplikasi CRUD pertama dengan Laravel. 🎉</p>',

            // Business article with tables and emphasis
            '<h2>Strategi Digital Marketing untuk UMKM di Tahun 2024</h2>
<p>Digital marketing menjadi kunci keberhasilan bisnis di era digital. Berikut adalah strategi yang terbukti efektif untuk UMKM:</p>
<h3>Platform yang Wajib Digunakan</h3>
<table>
<thead><tr><th>Platform</th><th>Keunggulan</th><th>Tipe Konten</th></tr></thead>
<tbody>
<tr><td><strong>Instagram</strong></td><td>Visual-first, engagement tinggi</td><td>Reels, Carousel, Stories</td></tr>
<tr><td><strong>TikTok</strong></td><td>Reach organik besar</td><td>Video pendek, Tutorial</td></tr>
<tr><td><strong>Facebook</strong></td><td>Iklan tertarget</td><td>Ads, Marketplace</td></tr>
<tr><td><strong>Google</strong></td><td>Search intent tinggi</td><td>SEO, Google Ads</td></tr>
</tbody>
</table>
<h3>5 Tips SEO untuk Pemula</h3>
<ol>
<li><strong>Riset keyword</strong> — Gunakan tools seperti Google Keyword Planner untuk menemukan kata kunci yang relevan</li>
<li><strong>Optimasi on-page</strong> — Pastikan title tag, meta description, dan heading sudah teroptimasi</li>
<li><strong>Mobile-friendly</strong> — Pastikan website responsif di semua perangkat</li>
<li><strong>Kecepatan loading</strong> — Optimasi gambar dan gunakan caching untuk mempercepat loading</li>
<li><strong>Konten berkualitas</strong> — Buat konten yang memberikan nilai nyata bagi pembaca</li>
</ol>
<blockquote><p>"Content is king, but engagement is queen, and the lady rules the palace." — Mari Smith</p></blockquote>
<p>Dengan menerapkan strategi ini secara konsisten, UMKM dapat meningkatkan <em>visibility</em> dan penjualan secara signifikan.</p>',

            // How-to article with steps
            '<h2>Cara Membuat API RESTful dengan Laravel</h2>
<p>API (Application Programming Interface) memungkinkan komunikasi antara berbagai aplikasi. Pada artikel ini, kita akan belajar membuat API menggunakan Laravel.</p>
<h3>Prasyarat</h3>
<ul>
<li>PHP 8.1 atau lebih tinggi</li>
<li>Composer terinstal</li>
<li>Laravel 10 atau lebih baru</li>
<li>Postman atau tool API testing lainnya</li>
</ul>
<h3>Langkah 1: Membuat Resource Controller</h3>
<p>Buka terminal di direktori proyek Laravel Anda dan jalankan:</p>
<pre><code>php artisan make:controller Api/BookController --api --model=Book</code></pre>
<p>Flag <code>--api</code> akan membuat controller tanpa metode <code>create()</code> dan <code>edit()</code> karena API tidak membutuhkan view.</p>
<h3>Langkah 2: Mendefinisikan Routes</h3>
<p>Buka file <code>routes/api.php</code> dan tambahkan route berikut:</p>
<pre><code>Route::apiResource(&#39;books&#39;, BookController::class);</code></pre>
<p>Perintah <code>apiResource</code> akan membuat route untuk:</p>
<ul>
<li><code>GET /api/books</code> — Index</li>
<li><code>POST /api/books</code> — Store</li>
<li><code>GET /api/books/{id}</code> — Show</li>
<li><code>PUT /api/books/{id}</code> — Update</li>
<li><code>DELETE /api/books/{id}</code> — Destroy</li>
</ul>
<h3>Langkah 3: Membuat Controller Logic</h3>
<p>Sekarang kita isi logika di controller. Berikut contoh metode <code>store()</code>:</p>
<pre><code>public function store(Request $request)
{
    $validated = $request-&gt;validate([
        &#39;title&#39; =&gt; &#39;required|string|max:255&#39;,
        &#39;author&#39; =&gt; &#39;required|string|max:255&#39;,
        &#39;isbn&#39; =&gt; &#39;required|string|unique:books,isbn&#39;,
    ]);

    $book = Book::create($validated);

    return response()-&gt;json([
        &#39;message&#39; =&gt; &#39;Book created successfully&#39;,
        &#39;data&#39; =&gt; $book,
    ], 201);
}</code></pre>
<h3>Langkah 4: Testing API</h3>
<p>Buka Postman atau Insomnia dan kirim request ke endpoint yang sudah dibuat. Pastikan untuk mengatur header <code>Content-Type: application/json</code>.</p>
<blockquote><p><strong>Tip:</strong> Gunakan <code>php artisan route:list</code> untuk melihat semua route yang sudah terdaftar.</p></blockquote>
<p>Dengan mengikuti langkah-langkah di atas, Anda sudah bisa membuat REST API yang fungsional dengan Laravel!</p>',

            // Opinion / editorial article
            '<h2>Mengapa Belajar Programming adalah Investasi Terbaik untuk Masa Depan</h2>
<p>Di tengah perkembangan teknologi yang semakin pesat, kemampuan memprogram menjadi salah satu skill paling berharga yang bisa dimiliki seseorang. Tidak hanya untuk karier di bidang teknologi, pemrograman juga membuka banyak peluang di berbagai industri.</p>
<h3>Peluang Karier yang Luas</h3>
<p>Menurut data dari <em>Bureau of Labor Statistics</em>, permintaan untuk software developer diproyeksikan meningkat sebesar <strong>25%</strong> pada tahun 2031 — jauh lebih cepat dari rata-rata pekerjaan lainnya. Beberapa peran yang bisa dijalankan:</p>
<ul>
<li><strong>Frontend Developer</strong> — Membangun antarmuka pengguna</li>
<li><strong>Backend Developer</strong> — Mengembangkan logika server dan database</li>
<li><strong>Full-Stack Developer</strong> — Mengurus seluruh stack teknologi</li>
<li><strong>DevOps Engineer</strong> — Mengelola infrastruktur dan deployment</li>
<li><strong>Data Scientist</strong> — Menganalisis data menggunakan kode</li>
</ul>
<h3>Gaji yang Kompetitif</h3>
<p>Gaji rata-rata software developer di Indonesia berkisar antara <strong>Rp 8 juta hingga Rp 30 juta</strong> per bulan, tergantung pengalaman dan lokasi. Di luar negeri, angkanya bisa jauh lebih tinggi lagi.</p>
<h3>Skill yang Bisa Dipelajari Secara Otonom</h3>
<p>Salah satu keunggulan belajar programming adalah banyaknya sumber belajar gratis yang tersedia:</p>
<ol>
<li><strong>FreeCodeCamp</strong> — Platform belajar coding gratis</li>
<li><strong>YouTube</strong> — Tutorial video tanpa biaya</li>
<li><strong>GitHub</strong> — Belajar dari source code open source</li>
<li><strong>MDN Web Docs</strong> — Dokumentasi resmi web technologies</li>
</ol>
<blockquote><p>"Everybody in this country should learn how to program a computer, because it teaches you how to think." — Steve Jobs</p></blockquote>
<p>Jadi, tidak ada alasan untuk tidak mulai belajar programming hari ini. Mulai dari yang kecil, konsisten, dan terus tingkatkan skill Anda. Masa depan sudah menunggu!</p>',

            // Feature article with blockquote and links
            '<h2>Tren Teknologi 2024: Apa yang Harus Anda Waspadai?</h2>
<p>Tahun 2024 membawa berbagai perkembangan teknologi yang signifikan. Berikut tren-tren utama yang perlu diperhatikan:</p>
<h3>1. Artificial Intelligence (AI)</h3>
<p>AI terus mengalami perkembangan pesat. tools seperti <strong>ChatGPT</strong>, <strong>Gemini</strong>, dan <strong>Claude</strong> telah mengubah cara kerja banyak industri. Beberapa aplikasi AI yang semakin populer:</p>
<ul>
<li>Penulisan konten dan copywriting</li>
<li>Pembuatan kode dan debugging</li>
<li>Analisis data dan prediksi</li>
<li>Desain grafis dan generasi gambar</li>
<li>Customer service dan chatbot</li>
</ul>
<h3>2. WebAssembly (Wasm)</h3>
<p><em>WebAssembly</em> memungkinkan bahasa seperti Rust, C++, dan Go untuk berjalan di browser dengan performa mendekati native. Ini membuka kemungkinan baru untuk aplikasi web yang sangat performant.</p>
<h3>3. Edge Computing</h3>
<p>Edge computing memindahkan pemrosesan data lebih dekat ke pengguna, mengurangi latency secara signifikan. Provider cloud seperti Cloudflare dan Vercel sudah mendukung edge functions.</p>
<h3>4. Web3 dan Blockchain</h3>
<p>Meskipun sempat mengalami penurunan popularitas, teknologi blockchain terus berkembang di sektor:</p>
<ol>
<li><strong>DeFi</strong> — Keuangan terdesentralisasi</li>
<li><strong>NFT</strong> — Sertifikasi kepemilikan digital</li>
<li><strong>DAO</strong> — Organisasi otonom terdesentralisasi</li>
<li><strong>Supply Chain</strong> — Pelacakan rantai pasok</li>
</ol>
<blockquote><p>"Technology is best when it brings people together." — Matt Mullenweg</p></blockquote>
<h3>Kesimpulan</h3>
<p>Mengikuti perkembangan teknologi bukan hanya tentang mengikuti tren, tetapi juga tentang memahami bagaimana teknologi dapat membantu menyelesaikan masalah nyata. Tetaplah belaskan dan eksplorasi teknologi baru secara bijak.</p>',

            // FAQ-style article
            '<h2>Pertanyaan Umum tentang PHP dan Laravel</h2>
<p>PHP masih menjadi salah satu bahasa pemrograman paling populer di dunia. Berikut beberapa pertanyaan umum yang sering ditanyakan:</p>
<h3>Apakah PHP masih relevan di tahun 2024?</h3>
<p><strong>Tentu saja!</strong> PHP masih power lebih dari 77% website di dunia, termasuk platform besar seperti:</p>
<ul>
<li><strong>WordPress</strong> — Menggerakkan 43% website global</li>
<li><strong>Facebook</strong> — Dibangun dengan PHP (Hack)</li>
<li><strong>Wikipedia</strong> — Encyclopedia terbesar di dunia</li>
<li><strong>Laravel</strong> — Framework PHP paling populer</li>
</ul>
<h3>Apa keunggulan Laravel dibanding framework lain?</h3>
<p>Laravel menawarkan banyak fitur bawaan yang mempermudah pengembangan:</p>
<ol>
<li><strong>Eloquent ORM</strong> — ORM yang sangat elegan dan intuitif</li>
<li><strong>Blade Templating</strong> — Template engine yang powerful</li>
<li><strong>Artisan CLI</strong> — Command-line tool untuk otomasi</li>
<li><strong>Built-in Authentication</strong> — Sistem autentikasi siap pakai</li>
<li><strong>Queue System</strong> — Pengelolaan background job</li>
<li><strong>Testing Support</strong> — PHPUnit integration yang seamless</li>
</ol>
<h3>Berapa lama waktu yang dibutuhkan untuk belajar Laravel?</h3>
<p>Waktu belajar tergantung pada fondasi PHP yang sudah dimiliki:</p>
<table>
<thead><tr><th>Tingkat</th><th>Durasi</th><th>Target</th></tr></thead>
<tbody>
<tr><td><strong>Pemula</strong></td><td>2-3 bulan</td><td>Bisa membuat CRUD sederhana</td></tr>
<tr><td><strong>Menengah</strong></td><td>6 bulan</td><td>Bisa membuat API &amp; auth</td></tr>
<tr><td><strong>Lanjut</strong></td><td>1+ tahun</td><td>Bisa arsitektur aplikasi besar</td></tr>
</tbody>
</table>
<h3>Apakah Laravel cocok untuk pemula?</h3>
<p><em>Absolutely!</em> Laravel memiliki dokumentasi yang sangat baik dan komunitas yang aktif di Indonesia. Banyak sumber belajar dalam Bahasa Indonesia yang tersedia gratis di internet.</p>
<blockquote><p>"Simplicity is the ultimate sophistication." — Leonardo da Vinci</p></blockquote>
<p>Jika Anda ingin memulai karier di web development, Laravel adalah pilihan yang sangat tepat.</p>',
        ];
    }
}
