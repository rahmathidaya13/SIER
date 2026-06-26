<?php

namespace Database\Seeders;

use App\Models\BranchModel;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branchesData = [
            'Jakarta (Cakung)' => [
                'address' => 'Green Sedayu Bizpark GS 1 No. 2, Jl. Cakung Cilincing KM. 2, Jakarta Timur',
                'phones' => [
                    '081318887231',
                    '08123386165',
                    '081233144166',
                    '082140003355',
                    '081222218616',
                    '081311155633',
                    '085319342111',
                    '081310495079',
                    '081318229800'
                ]
            ],

            'Jakarta (Pulogadung)' => [
                'address' => 'Ruko PTC (Pulogadung Trade Center) Blok 8i Kav. 23, Jl. Raya Bekasi KM. 21, Kel. Rawa Terate, Kec. Cakung, Jakarta Timur, 13920',
                'phones' => [
                    '081219000714',
                    '081219000713',
                    '08135045553'
                ]
            ],

            'Jakarta (Jagakarsa)' => [
                'address' => 'Ruko Ciganjur Express, Jl. Moh. Kahfi 1 No.90C, Ciganjur, Kec. Jagakarsa, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta, 12630',
                'phones' => [
                    '08135045553'
                ]
            ],

            'Bekasi' => [
                'address' => 'Ruko Emerald Blok UG No 52, Jl Boulevard Selatan Summarecon, Bekasi Jawa Barat',
                'phones' => [
                    '081218612009',
                    '081242422289',
                    '081218612008'
                ]
            ],

            'Bandung' => [
                'address' => 'Komplek Metro Trade Center (MTC / Metro Indah Mall) Blok K No. 6 Jl Soekarno Hatta By Pass, Bandung',
                'phones' => [
                    '082214002302',
                    '081313091383',
                    '082214002303'
                ]
            ],

            'Bogor' => [
                'address' => 'Ruko Galaxy, Jl. Raya Tajur No. 59 Blok B, Kec. Bogor Timur, Kota Bogor, Jawa Barat',
                'phones' => [
                    '081294928652',
                    '081294928653',
                    '081385715719',
                    '081293981113'
                ]
            ],

            'Malang' => [
                'address' => 'Ruko Laguna Kav 6-7 Jl S.P. Sudarmo 31 Malang – Jawa Timur – Indonesia',
                'phones' => [
                    '081216665445',
                    '081216665454',
                    '081333394799'
                ]
            ],

            'Surabaya' => [
                'address' => 'Ruko Ngagel,  Jl. Ngagel Raya no.77 MM Surabaya',
                'phones' => [
                    '081282993727',
                    '081233144177',
                    '081357999261'
                ]
            ],

            'Blitar' => [
                'address' => 'Jl Veteran No 172 Blitar – Jawa Timur',
                'phones' => [
                    '081334300036',
                    '081334300038',
                    '081331709910'
                ]
            ],

            'Yogyakarta' => [
                'address' => 'Jl Brigjen Katamso No 242,  Yogyakarta',
                'phones' => [
                    '085200667148',
                    '081326870111',
                    '081282993447'
                ]
            ],

            'Semarang' => [
                'address' => 'Jl.Gajah Raya No 161 Blok C, Semarang,Jawa Tengah',
                'phones' => [
                    '081224422653',
                    '082123477709',
                    '081224422563'
                ]
            ],

            'Palembang' => [
                'address' => 'Jl Pangeran Ayin No 319 B, Sako Kenten laut (depan Indo Grosir), Palembang, Sumatera Selatan',
                'phones' => [
                    '081367407078',
                    '081281268272',
                    '081367407079'
                ]
            ],

            'Medan' => [
                'address' => 'Jl. Setiabudi No 275 C Tanjungsari, Medan',
                'phones' => [
                    '081314025406',
                    '081314025405',
                    '081384196933',
                    '082167734018'
                ]
            ],

            'Pekanbaru' => [
                'address' => ' Jl. Arifin Ahmad Ruko No. 99 D Pekanbaru, Riau',
                'phones' => [
                    '082321950002',
                    '082111443284',
                    '081311177522'
                ]
            ],

            'Makassar (Perintis)' => [
                'address' => 'Jl. Perintis Kemerdekaan, Ruko Bukit Mas Petak 7, Kel. Tamanlarea Jaya, Kec. Tamanlarea, Kota Makassar, Sulawesi Selatan',
                'phones' => [
                    '082292249005',
                    '081354485755',
                    '082260000883',
                    '081356680616',
                    '081219430954'
                ]
            ],

            'Banjarmasin' => [
                'address' => 'Ruko STIH Sultan Adam No. 7, Jl. Sultan Adam No. 130 (Simpang Empat Lampu Merah Sungai Andai) Banjarmasin – Kalimantan Selatan',
                'phones' => [
                    '081253323982',
                    '082124473388'
                ]
            ],

            'Denpasar' => [
                'address' => 'Jl.Mahendradata 198 B, Denpasar, Bali',
                'phones' => [
                    '082236620334',
                    '082236620335',
                    '082144025417'
                ]
            ],
        ];

        foreach ($branchesData as $branchName => $data) {
            // Tentukan status official
            $statusOfficial = ($branchName === 'Jakarta (Cakung)') ? 'official' : 'unofficial';
            // 1. Buat atau Ambil Data Cabang (Parent)
            $branch = BranchModel::firstOrCreate(
                ['name' => $branchName],
                [
                    'id_branch' => Str::uuid(),
                    'branch_code' => BranchModel::generateUniqueCode(),
                    'name'        => $branchName,
                    'address'     => $data['address'],
                    'status' => $statusOfficial
                ]
            );

            $branch->update([
                'address' => $data['address'],
            ]);

            // 2. Loop nomor telepon dan insert via relasi
            foreach ($data['phones'] as $phone) {
                // Saya gunakan firstOrCreate agar jika seeder dijalankan 2x, nomor tidak duplikat
                // Pastikan ganti 'phone_number' sesuai nama kolom di tabel branch_phones kamu
                $branch->branchPhone()->firstOrCreate(
                    ['phone' => $phone],
                );
            }
        }
    }
}
