<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArDataSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Plant 1511 · Mega
            ['plant'=>'1511','customer_id'=>'3000000305','customer_name'=>'AMCOR FLEXIBLES INDONESIA','collection_by'=>'Mega','current'=>22755000,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>22755000,'so_without_od'=>0,'so_with_od'=>5,'total_so'=>5,'ar_target'=>0,'ar_actual'=>91575000],
            ['plant'=>'1511','customer_id'=>'3000006597','customer_name'=>'ANEKA KENCANA PLASTINDO','collection_by'=>'Mega','current'=>1100010000,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>1100010000,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>1190475000,'ar_actual'=>501720000],
            ['plant'=>'1511','customer_id'=>'3000009691','customer_name'=>'BUDI STARCH & SWEETENER TBK','collection_by'=>'Mega','current'=>223776000,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>223776000,'so_without_od'=>1,'so_with_od'=>3,'total_so'=>4,'ar_target'=>359640000,'ar_actual'=>539460000],
            ['plant'=>'1511','customer_id'=>'3000012902','customer_name'=>'CIPTA KARYA SUKSES ABADI','collection_by'=>'Mega','current'=>865245000,'days_1_30'=>220890000,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>1086135000,'so_without_od'=>19,'so_with_od'=>2,'total_so'=>21,'ar_target'=>449550000,'ar_actual'=>449550000],
            ['plant'=>'1511','customer_id'=>'3000017065','customer_name'=>'GUANLONG PACKINGS INDONESIA','collection_by'=>'Mega','current'=>2275500000,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>2275500000,'so_without_od'=>1,'so_with_od'=>18,'total_so'=>19,'ar_target'=>999000000,'ar_actual'=>1110000000],
            ['plant'=>'1511','customer_id'=>'3000002495','customer_name'=>'INDOFOOD CBP SUKSES MAKMUR Tbk','collection_by'=>'Mega','current'=>1569262500,'days_1_30'=>9090900,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>1578353400,'so_without_od'=>0,'so_with_od'=>13,'total_so'=>13,'ar_target'=>827244150,'ar_actual'=>1401735750],
            ['plant'=>'1511','customer_id'=>'3000002784','customer_name'=>'JAYA NURIMBA','collection_by'=>'Mega','current'=>1528470000,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>1528470000,'so_without_od'=>5,'so_with_od'=>7,'total_so'=>12,'ar_target'=>0,'ar_actual'=>0],
            ['plant'=>'1511','customer_id'=>'3000007508','customer_name'=>'MITRA KARYA PLASTINDO','collection_by'=>'Mega','current'=>123465744,'days_1_30'=>73730640,'days_30_60'=>0,'days_60_90'=>115896787,'days_over_90'=>316934663,'total'=>630027834,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>0,'ar_actual'=>75500000],
            ['plant'=>'1511','customer_id'=>'3000004476','customer_name'=>'PRALON','collection_by'=>'Mega','current'=>490768296,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>490768296,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>247531943,'ar_actual'=>119769000],
            ['plant'=>'1511','customer_id'=>'3000005866','customer_name'=>'TIRTA ALAM SEGAR','collection_by'=>'Mega','current'=>224430900,'days_1_30'=>660156960,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>884587860,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>892462200,'ar_actual'=>1047974310],
            // Plant 1511 · Viona
            ['plant'=>'1511','customer_id'=>'3000000574','customer_name'=>'ASIETEX SINAR INDOPRATAMA','collection_by'=>'Viona','current'=>1086626166,'days_1_30'=>317009007,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>1403635173,'so_without_od'=>0,'so_with_od'=>14,'total_so'=>14,'ar_target'=>248099674,'ar_actual'=>270296633],
            ['plant'=>'1511','customer_id'=>'3000025902','customer_name'=>'BAROKAH MITRA USAHA LANCAR','collection_by'=>'Viona','current'=>0,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>211412178,'total'=>211412178,'so_without_od'=>2,'so_with_od'=>3,'total_so'=>5,'ar_target'=>278401592,'ar_actual'=>0],
            ['plant'=>'1511','customer_id'=>'3000001614','customer_name'=>'EASTERNTEX','collection_by'=>'Viona','current'=>463384584,'days_1_30'=>616063864,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>1079448448,'so_without_od'=>0,'so_with_od'=>1,'total_so'=>1,'ar_target'=>412456275,'ar_actual'=>0],
            ['plant'=>'1511','customer_id'=>'3000002003','customer_name'=>'GLORIA ORIGITA COSMETICS','collection_by'=>'Viona','current'=>230594230,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>230594230,'so_without_od'=>0,'so_with_od'=>4,'total_so'=>4,'ar_target'=>135515282,'ar_actual'=>137470780],
            ['plant'=>'1511','customer_id'=>'3000002879','customer_name'=>'KAHATEX','collection_by'=>'Viona','current'=>990326016,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>990326016,'so_without_od'=>0,'so_with_od'=>9,'total_so'=>9,'ar_target'=>435031200,'ar_actual'=>0],
            // Plant 1512 · Miya
            ['plant'=>'1512','customer_id'=>'3000000735','customer_name'=>'BASF INDONESIA','collection_by'=>'Miya','current'=>4919670921,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>4919670921,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>3216693776,'ar_actual'=>1130685648],
            ['plant'=>'1512','customer_id'=>'3000007206','customer_name'=>'EAGLE INDO PHARMA','collection_by'=>'Miya','current'=>3427948359,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>3427948359,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>3250891910,'ar_actual'=>2067427066],
            ['plant'=>'1512','customer_id'=>'3000002277','customer_name'=>'HENKEL ADHESIVE TECHNOLOGIES','collection_by'=>'Miya','current'=>1439753250,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>1439753250,'so_without_od'=>0,'so_with_od'=>5,'total_so'=>5,'ar_target'=>360930375,'ar_actual'=>360389250],
            ['plant'=>'1512','customer_id'=>'3000023872','customer_name'=>'PUPUK LAPAN HARSA','collection_by'=>'Miya','current'=>14012935538,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>14012935538,'so_without_od'=>0,'so_with_od'=>7,'total_so'=>7,'ar_target'=>0,'ar_actual'=>0],
            ['plant'=>'1512','customer_id'=>'3000004612','customer_name'=>'PZ CUSSONS INDONESIA','collection_by'=>'Miya','current'=>2340146466,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>2340146466,'so_without_od'=>0,'so_with_od'=>11,'total_so'=>11,'ar_target'=>1620745365,'ar_actual'=>1004271678],
            ['plant'=>'1512','customer_id'=>'3000006208','customer_name'=>'VICTORIA CARE INDONESIA TBK','collection_by'=>'Miya','current'=>2858595627,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>2858595627,'so_without_od'=>0,'so_with_od'=>16,'total_so'=>16,'ar_target'=>1465499591,'ar_actual'=>1927252148],
            // Plant 1515 · Risa
            ['plant'=>'1515','customer_id'=>'3000002090','customer_name'=>'GUNUNG MELAYU','collection_by'=>'Risa','current'=>486647144,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>486647144,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>0,'ar_actual'=>0],
            ['plant'=>'1515','customer_id'=>'3000003933','customer_name'=>'MUSIM MAS','collection_by'=>'Risa','current'=>832027140,'days_1_30'=>13286700,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>845313840,'so_without_od'=>2,'so_with_od'=>3,'total_so'=>5,'ar_target'=>34265700,'ar_actual'=>0],
            ['plant'=>'1515','customer_id'=>'3000005561','customer_name'=>'SUPRA MATRA ABADI','collection_by'=>'Risa','current'=>1276400933,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>1276400933,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>0,'ar_actual'=>0],
            // Plant 1516 · Mega
            ['plant'=>'1516','customer_id'=>'3000012169','customer_name'=>'AVIA AVIAN INDUSTRI PIPA','collection_by'=>'Mega','current'=>635697000,'days_1_30'=>0,'days_30_60'=>0,'days_60_90'=>0,'days_over_90'=>0,'total'=>635697000,'so_without_od'=>0,'so_with_od'=>0,'total_so'=>0,'ar_target'=>0,'ar_actual'=>0],
        ];

        $period = '2026-01-31';
        $now    = now();

        DB::table('ar_data')->insert(
            array_map(fn($r) => array_merge($r, ['period' => $period, 'created_at' => $now, 'updated_at' => $now]), $rows)
        );
    }
}
