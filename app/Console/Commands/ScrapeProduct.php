<?php

namespace App\Console\Commands;

use Goutte\Client;
use App\Models\BranchModel;
use Illuminate\Support\Str;
use App\Models\ProductModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;

class ScrapeProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scraping data produk dari tokomesin.com';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // === CLIENT DENGAN TIMEOUT, RETRY, REFERER ===
        $client = new Client(HttpClient::create([
            'headers' => [
                'User-Agent' => $this->randomUserAgent(),
                'Referer' => 'https://www.google.com',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
            'timeout' => 15,
        ]));

        $categories = $this->categoryProduct();
        $baseUrl = 'https://www.tokomesin.com/product-category';
        $results = [];

        foreach ($categories as $category) {

            Log::info("\n=== SCRAPING CATEGORY: $category ===");
            $this->info("\n=== SCRAPING CATEGORY: $category ===");

            $page = 1;
            $prevFirstProduct = null;

            while (true) {
                $url = $page == 1
                    ? "$baseUrl/$category"
                    : "$baseUrl/$category/page/$page";

                Log::info("\n[SCRAPING FROM] $url");
                $this->info("\n[SCRAPING FROM] $url");

                // === REQUEST PAGE ===
                try {
                    $crawler = $client->request('GET', $url);
                } catch (\Exception $e) {
                    Log::error("Request failed: $url - " . $e->getMessage());
                    $this->error("Request failed: $url - " . $e->getMessage());
                    break;
                }

                // === PROCESS PRODUCTS ===
                $productNodes = $crawler->filter('li.product');
                if ($productNodes->count() === 0) {
                    Log::info("Tidak ada produk lagi di category {$category}. STOP.");
                    $this->info("Tidak ada produk lagi di category {$category}. STOP.");
                    break;
                }
                // === CEGAH PAGINATION LOOPING ===
                $currentFirstProduct = trim(
                    $productNodes->first()->filter('.woocommerce-loop-product__title')->text()
                );

                // Cegah infinite loop
                if ($prevFirstProduct === $currentFirstProduct) {
                    Log::info("Halaman sama → STOP ({$category})");
                    $this->info("Halaman sama → STOP ({$category})");
                    break;
                }

                $prevFirstProduct = $currentFirstProduct;

                // ================================================================
                //  LOOP PRODUK
                // ================================================================
                $productNodes->each(function ($node) use (&$client, &$category, &$results) {

                    // nama produk
                    $namaNode = $node->filter('.woocommerce-loop-product__title');
                    if (!$namaNode->count()) return;
                    $nama = trim($namaNode->text());
                    // end nama produk

                    // --- harga produk ---
                    $hargaNode = $node->filter('.price');
                    $priceOriginal = null;
                    $priceDiscount = null;

                    if ($hargaNode->count()) {
                        $origNode = $hargaNode->filter('del .woocommerce-Price-amount');
                        $discNode = $hargaNode->filter('ins .woocommerce-Price-amount');

                        if ($origNode->count())
                            $priceOriginal = (int) preg_replace('/[^\d]/', '', $origNode->text());

                        if ($discNode->count())
                            $priceDiscount = (int) preg_replace('/[^\d]/', '', $discNode->text());

                        if (!$priceOriginal && !$priceDiscount) {
                            $priceOriginal = (int) preg_replace('/[^\d]/', '', $hargaNode->text());
                        }
                    }
                    // --- end harga produk ---

                    // --- link produk ---
                    $link = $node->filter('a')->first()->attr('href') ?? null;
                    // --- end link produk ---

                    // gambar utama
                    $imgNode = $node->filter('img')->first();
                    $img = $imgNode->attr('data-src')
                        ?? $imgNode->attr('data-lazy-src')
                        ?? $imgNode->attr('src');
                    // end gambar utama

                    // --- gallery images ---
                    // $galleryImages = $this->scrapeGallery($client, $link);
                    // --- end gallery images ---

                    // --- description ---
                    // $description = $this->scrapeDescription($client, $link);
                    // --- end description ---
                    $categoryFormatted = strtolower(str_replace([' ', '/'], '-', $category));
                    // ✔ Gunakan updateOrCreate agar tidak ganda
                    try {
                        $existing = ProductModel::where('link', $link)->first();

                        if (!$existing) {

                            $product = ProductModel::create([
                                'source' => 'scrape',
                                'link' => $link,
                                'name' => $nama,
                                'item_condition' => 'new',
                                'slug' => Str::slug($nama),
                                'image_link' => $img,
                                'category' => $categoryFormatted,
                            ]);

                            $basePrice = $priceOriginal ?? 0;
                            $discPrice = $priceDiscount ?? 0;
                            $discountPercentage = 0; // Default 0%

                            if ($basePrice > 0 && $discPrice > 0 && $discPrice < $basePrice) {
                                // Rumus: ((Harga Asli - Harga Diskon) / Harga Asli) * 100
                                $saving = $basePrice - $discPrice;
                                $discountPercentage = (int) round(($saving / $basePrice) * 100);
                            }

                            $product->prices()->create([
                                'product_id' => $product->product_id,
                                'base_price' =>  $basePrice,
                                'discount_percentage' => $discountPercentage,
                                'discount_price' => $discPrice,
                                'price_type' => isset($discPrice) && $discPrice > 0 ? 'discount' : 'normal',

                            ]);


                            Log::info("\n[BARU] {$nama}");
                            $this->info("\n[BARU] {$nama}");
                            return;
                        }

                        $changes = [];

                        $fields = [
                            'source' => 'scrape',
                            'name' => $nama,
                            'image_link' => $img,
                            'category' => $categoryFormatted,
                            'link' => $link,
                        ];

                        foreach ($fields as $key => $newValue) {

                            $oldValue = $existing->{$key};

                            // bandingkan array gallery
                            // if ($key === 'image_url') {
                            //     if ($oldValue !== $newValue) {
                            //         $changes[$key] = $newValue;
                            //     }
                            //     continue;
                            // }

                            // bandingkan normal field
                            if ($oldValue != $newValue) {
                                $changes[$key] = $newValue;
                            }
                        }

                        if (!empty($changes)) {
                            $existing->update($changes);

                            Log::info("\n[UPDATE] {$nama} → Perubahan: " . implode(", ", array_keys($changes)));
                            $this->info("\n[UPDATE] {$nama} → Perubahan: " . implode(", ", array_keys($changes)));
                        } else {
                            Log::info("\n[SKIP] {$nama} (tidak ada perubahan)");
                            $this->info("\n[SKIP] {$nama} (tidak ada perubahan)");
                        }
                    } catch (\Throwable $e) {
                        Log::error("Gagal simpan ($nama): " . $e->getMessage());
                        $this->error("Gagal simpan ($nama): " . $e->getMessage());
                    }
                    $results[] = [
                        'source' => 'scrape',
                        'name' => $nama,
                        'image_link' => $img,
                        'category' => $categoryFormatted,
                        'link' => $link,
                    ];
                });
                $page++;
                sleep(rand(1, 3));
            }
        }

        Log::info("Scraping selesai. Total produk: " . count($results));
        $this->info("Scraping selesai. Total produk: " . count($results));
    }
    private function categoryProduct()
    {
        return [
            'mesin-makanan',
            'mesin-minuman',
            'mesin-pengemas',
            'mesin-bakery',
            'mesin-baru',
            'alat-dapur-modern',
            'mesin-industri',
            'mesin-pertanian/mesin-cetak-pelet-apung',
            'mesin-pertanian/mesin-grinder-kompos-organik',
            'mesin-pertanian/mesin-oven-pengering',
            'mesin-pertanian/mesin-pasteurisasi',
            'mesin-pertanian/mesin-pelubang-tanah',
            'mesin-pertanian/mesin-pemanen-padi',
            'mesin-pertanian/mesin-pembuat-dodol',
            'mesin-pertanian/mesin-penanam',
            'mesin-pertanian/mesin-pencacah-rumput-chopper',
            'mesin-pertanian/mesin-penepung-diskmill',
            'mesin-pertanian/mesin-penepung-hammer-mill',
            'mesin-pertanian/mesin-pengering-padi',
            'mesin-pertanian/mesin-pengolah-madu',
            'mesin-pertanian/mesin-pengupas-dan-pemipil-jagung',
            'mesin-pertanian/mesin-pengupas-kacang',
            'mesin-pertanian/mesin-pengupas-kopi',
            'mesin-pertanian/mesin-pengupas-kulit-ari',
            'mesin-pertanian/mesin-pengupas-padi',
            'mesin-pertanian/mesin-peternakan',
            'mesin-pertanian/mesin-sangrai-kopi',
            'mesin-pertanian/penetas-telur',
        ];
    }
    private function randomUserAgent()
    {
        // === ROTATING USER AGENT ===
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14 Safari/605.1.15',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
            'Mozilla/5.0 (Linux; Android 11; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0 Mobile Safari/537.36',
        ];
        return $agents[array_rand($agents)];
    }
}
