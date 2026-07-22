<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class AgroProductSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar que todas las categorías necesarias existen
        $categoryData = [
            'Fertilizantes'    => 'Fertilizantes y abonos para suelos y cultivos',
            'Semillas'         => 'Semillas certificadas de hortalizas, granos y cereales',
            'Plaguicidas'      => 'Control químico de plagas e insectos',
            'Herbicidas'       => 'Control de malezas y hierbas no deseadas',
            'Fungicidas'       => 'Control de hongos y enfermedades foliares',
            'Herramientas'     => 'Herramientas manuales para trabajo agrícola',
            'Equipos de Riego' => 'Sistemas y componentes de riego tecnificado',
            'Insecticidas'     => 'Productos para control específico de insectos',
        ];

        $cats = [];
        foreach ($categoryData as $name => $desc) {
            $cat = Category::firstOrCreate(['name' => $name], ['description' => $desc]);
            $cats[$name] = $cat->id;
        }

        // Pools de imágenes por categoría (Unsplash, cicladas con módulo)
        $imgPool = [
            'Fertilizantes' => [
                'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1523741543316-beb7fc7023d8?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1495107334309-fcf20504a5ab?w=400&h=300&fit=crop',
            ],
            'Semillas' => [
                'https://images.unsplash.com/photo-1551754655-cd27e38d2076?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1597916829826-02e5bb4a54e0?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1592841200221-a6898f307baa?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1536236005651-b5e36945a0a9?w=400&h=300&fit=crop',
            ],
            'Plaguicidas' => [
                'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&h=300&fit=crop',
            ],
            'Herbicidas' => [
                'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1523741543316-beb7fc7023d8?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=400&h=300&fit=crop',
            ],
            'Fungicidas' => [
                'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1495107334309-fcf20504a5ab?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&h=300&fit=crop',
            ],
            'Herramientas' => [
                'https://images.unsplash.com/photo-1535379453347-1ffd615e2e08?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1601598851547-4302969d0614?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1416169607655-0c2b3ce2e1cc?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=400&h=300&fit=crop',
            ],
            'Equipos de Riego' => [
                'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1586771107445-d3ca888129ff?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=400&h=300&fit=crop',
            ],
            'Insecticidas' => [
                'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?w=400&h=300&fit=crop',
                'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&h=300&fit=crop',
            ],
        ];

        // 100 productos de agroservicio
        // Formato: [cat, code, name, desc, buy, sell, stock, unit]
        $products = [
            // ── FERTILIZANTES (20) ─────────────────────────────────────────
            ['Fertilizantes','F001','Fertilizante Nitrogenado NPK','Fertilizante NPK balanceado para todo tipo de cultivos',10.50,15.00,50,'kg'],
            ['Fertilizantes','F002','Urea 46%','Nitrógeno de alta concentración, granulado pronto efecto',8.00,12.50,200,'kg'],
            ['Fertilizantes','F003','Sulfato de Amonio 21%','Fuente de nitrógeno y azufre, pH neutro',7.50,11.00,150,'kg'],
            ['Fertilizantes','F004','Nitrato de Potasio 13-0-46','Fuente de potasio y nitrógeno soluble en agua',14.00,20.00,80,'kg'],
            ['Fertilizantes','F005','Superfosfato Triple 46%','Fósforo de alta concentración para raíces fuertes',9.00,14.00,120,'kg'],
            ['Fertilizantes','F006','Cloruro de Potasio 60%','Potasio para mejorar calidad y resistencia del cultivo',8.50,13.00,100,'kg'],
            ['Fertilizantes','F007','Fertilizante Foliar 8-16-40','Fórmula especial para etapa de fructificación',18.00,26.00,60,'kg'],
            ['Fertilizantes','F008','Sulfato de Magnesio Epsom','Fuente de magnesio y azufre para clorofila activa',6.00,9.50,90,'kg'],
            ['Fertilizantes','F009','Ácido Húmico y Fúlvico','Mejora la estructura del suelo y absorción de nutrientes',22.00,32.00,40,'lt'],
            ['Fertilizantes','F010','Aminoácidos Foliares Premium','Estimulante de crecimiento y resistencia al estrés',25.00,38.00,30,'lt'],
            ['Fertilizantes','F011','Calcio-Boro Complex','Previene pudrición apical y mejora cuaje de frutos',20.00,30.00,45,'lt'],
            ['Fertilizantes','F012','Nitrato de Calcio 15.5-0-0','Calcio soluble para nutrición foliar y radicular',11.00,16.50,70,'kg'],
            ['Fertilizantes','F013','MAP Fosfato Monoamónico 12-61-0','Fósforo y nitrógeno altamente solubles',15.00,22.00,55,'kg'],
            ['Fertilizantes','F014','DAP Fosfato Diamónico 18-46-0','Arranque radicular, alto contenido de fósforo',13.00,19.00,65,'kg'],
            ['Fertilizantes','F015','Compost Orgánico Certificado','Mejora física, química y biológica del suelo',5.00,8.00,300,'kg'],
            ['Fertilizantes','F016','Humus de Lombriz Peletizado','Abono orgánico de alta disponibilidad nutricional',6.50,10.00,200,'kg'],
            ['Fertilizantes','F017','Biofertilizante Rizobium Soya','Fijación biológica de nitrógeno para leguminosas',12.00,18.00,50,'kg'],
            ['Fertilizantes','F018','Zinc EDTA 14% Quelado','Micronutriente esencial para síntesis de proteínas',28.00,42.00,25,'kg'],
            ['Fertilizantes','F019','Hierro EDDHA 6% Quelado','Corrector de clorosis férrica en suelos calcáreos',35.00,52.00,20,'kg'],
            ['Fertilizantes','F020','Boro Etanolamina 15%','Micronutriente para floración y cuaje de frutos',24.00,36.00,30,'lt'],

            // ── SEMILLAS (19) ──────────────────────────────────────────────
            ['Semillas','S001','Semilla Maíz Híbrido DK-7088','Maíz híbrido de alto rendimiento, tolerante a sequía',2.00,3.50,100,'kg'],
            ['Semillas','S002','Semilla Frijol Negro Talamanca','Variedad criolla mejorada, ciclo corto 75 días',3.00,5.00,80,'kg'],
            ['Semillas','S003','Semilla Tomate Manzano Shanty','Indeterminado, alta producción, resistente a virus',18.00,28.00,15,'sobre'],
            ['Semillas','S004','Semilla Chile Jalapeño M','Picor intenso, fruto grande, ciclo 90 días',15.00,24.00,20,'sobre'],
            ['Semillas','S005','Semilla Lechuga Americana Great Lakes','Cabeza compacta, tolerante al calor',12.00,19.00,25,'sobre'],
            ['Semillas','S006','Semilla Zanahoria Nantes 3','Raíz cilíndrica, dulce, uniforme y sin corazón',10.00,16.00,30,'sobre'],
            ['Semillas','S007','Semilla Cebolla Cabezona Roja','Bulbo grande, piel roja intensa, larga vida útil',11.00,17.00,25,'sobre'],
            ['Semillas','S008','Semilla Papa Diacol Capiro R12','Papa criolla amarilla, resistente al tizón tardío',4.50,7.00,50,'kg'],
            ['Semillas','S009','Semilla Pepino Marketmore 76','Pepino tipo americano, piel oscura, sin amargor',13.00,20.00,20,'sobre'],
            ['Semillas','S010','Semilla Sandía Charleston Grey','Sandía sin semillas, pulpa roja intensa, 14 kg',14.00,22.00,18,'sobre'],
            ['Semillas','S011','Semilla Melón Cantalupo Hales Best','Pulpa naranja aromática, ciclo 85 días',13.00,20.00,18,'sobre'],
            ['Semillas','S012','Semilla Maíz Dulce Temprano','Extra dulce, cosecha a los 70 días',3.00,5.00,40,'kg'],
            ['Semillas','S013','Semilla Pimiento Morrón California Wonder','Fruto grande cuadrado, color rojo al madurar',16.00,25.00,20,'sobre'],
            ['Semillas','S014','Semilla Cilantro Bouquet','Follaje denso, aroma intenso, ciclo 30 días',5.00,8.00,35,'sobre'],
            ['Semillas','S015','Semilla Rábano Cherry Belle','Raíz redonda roja, lista en 25 días',6.00,9.50,30,'sobre'],
            ['Semillas','S016','Semilla Espinaca Atlanta','Hoja oscura lisa, resistente al calor y bolting',10.00,16.00,25,'sobre'],
            ['Semillas','S017','Semilla Repollo Verde Copenhagen','Cabeza compacta y densa, ciclo 85 días',9.00,14.00,25,'sobre'],
            ['Semillas','S018','Semilla Brócoli Waltham 29','Cabeza uniforme azul-verde, ciclo 90 días',11.00,17.00,22,'sobre'],
            ['Semillas','S019','Semilla Calabaza Butternut Waltham','Fruto cilíndrico beige, pulpa naranja dulce',8.00,13.00,28,'sobre'],

            // ── PLAGUICIDAS (15) ───────────────────────────────────────────
            ['Plaguicidas','P001','Plaguicida ABC Control Plus','Control de insectos masticadores y chupadores',12.00,18.00,30,'lt'],
            ['Plaguicidas','P002','Clorpirifos 480 EC','Insecticida organofosforado amplio espectro',9.50,15.00,40,'lt'],
            ['Plaguicidas','P003','Lambda-Cihalotrina 2.5 EC','Piretroide de acción rápida contra lepidópteros',14.00,21.00,25,'lt'],
            ['Plaguicidas','P004','Imidacloprid 70 WS','Sistémico para trips, mosca blanca, áfidos',22.00,33.00,20,'kg'],
            ['Plaguicidas','P005','Abamectina 1.8 EC','Acaricida e insecticida para minador y ácaros',30.00,45.00,15,'lt'],
            ['Plaguicidas','P006','Spinosad 120 SC','Biológico para thrips y mosca de la fruta',35.00,52.00,12,'lt'],
            ['Plaguicidas','P007','Cipermetrina 25 EC','Piretroide de contacto e ingestión, amplio espectro',8.50,13.00,35,'lt'],
            ['Plaguicidas','P008','Deltametrina 2.5 EC','Insecticida piretroide para plagas del suelo',10.00,15.50,30,'lt'],
            ['Plaguicidas','P009','Acetamiprid 20 SP','Neonicotinoide sistémico para insectos chupadores',18.00,27.00,22,'kg'],
            ['Plaguicidas','P010','Thiamethoxam 25 WG','Tratamiento semilla y aplicación foliar sistémica',20.00,30.00,18,'kg'],
            ['Plaguicidas','P011','Profenofos 50 EC','Organofosforado para trips, mosca blanca, ácaros',12.00,18.50,28,'lt'],
            ['Plaguicidas','P012','Bifentrina 10 EC','Piretroide para ácaros y un amplio rango de insectos',13.00,20.00,25,'lt'],
            ['Plaguicidas','P013','Esfenvalerato 5 EC','Insecticida piretroide para plagas de almacén',11.00,17.00,30,'lt'],
            ['Plaguicidas','P014','Metoxifenocida 24 SC','Ecdisona mimétic para lepidópteros, bajo riesgo',28.00,42.00,15,'lt'],
            ['Plaguicidas','P015','Indoxacarb 30 WG','Control selectivo de orugas y larvas de lepidópteros',32.00,48.00,12,'kg'],

            // ── HERBICIDAS (12) ────────────────────────────────────────────
            ['Herbicidas','HB001','Glifosato 48% SL','Herbicida sistémico no selectivo para maleza general',6.50,10.00,100,'lt'],
            ['Herbicidas','HB002','Paraquat 20% SL','Herbicida de contacto de acción rápida',8.00,12.50,60,'lt'],
            ['Herbicidas','HB003','2,4-D Amina 72% SL','Hormonal selectivo para malezas de hoja ancha',5.50,8.50,80,'lt'],
            ['Herbicidas','HB004','Atrazina 80% WG','Pre y post emergente selectivo para maíz',7.00,11.00,50,'kg'],
            ['Herbicidas','HB005','Metribuzín 70% WG','Selectivo para papa, soya y tomate',12.00,18.00,30,'kg'],
            ['Herbicidas','HB006','Pendimetalín 33% EC','Pre emergente para gramíneas y hoja ancha',9.00,14.00,45,'lt'],
            ['Herbicidas','HB007','Oxifluorfén 24% EC','Pre emergente en cebollas, ajo y cultivos leñosos',10.00,15.50,35,'lt'],
            ['Herbicidas','HB008','Diuron 80% WP','Control en cultivos de caña, algodón y café',8.00,12.50,40,'kg'],
            ['Herbicidas','HB009','Bentazon 48% SL','Post emergente selectivo en granos básicos',11.00,17.00,32,'lt'],
            ['Herbicidas','HB010','Fluazifop-P-Butil 15% EC','Post emergente para gramíneas en cultivos de hoja ancha',14.00,21.00,25,'lt'],
            ['Herbicidas','HB011','Quizalofop-P-Etil 10% EC','Selectivo para gramíneas en soya y algodón',15.00,23.00,22,'lt'],
            ['Herbicidas','HB012','Nicosulfurón 40 SC','Post emergente para maíz en estado temprano',18.00,27.00,20,'lt'],

            // ── FUNGICIDAS (10) ────────────────────────────────────────────
            ['Fungicidas','FG001','Mancozeb 80% WP','Fungicida de contacto multisite preventivo',7.00,11.00,60,'kg'],
            ['Fungicidas','FG002','Clorotalonil 72% SC','Control de tizón temprano y tardío en hortalizas',8.50,13.00,50,'lt'],
            ['Fungicidas','FG003','Propineb 70% WP','Preventivo y curativo de amplio espectro',9.00,14.00,45,'kg'],
            ['Fungicidas','FG004','Oxicloruro de Cobre 50% WP','Bactericida y fungicida cúprico natural',6.00,9.50,55,'kg'],
            ['Fungicidas','FG005','Metalaxil + Mancozeb 64% WP','Sistémico + contacto para Phytophthora y mildiu',12.00,18.50,35,'kg'],
            ['Fungicidas','FG006','Tebuconazol 250 EW','Triazol sistémico para royas y oidio',22.00,33.00,25,'lt'],
            ['Fungicidas','FG007','Propiconazol 25% EC','Triazol preventivo y curativo para cereales',18.00,27.00,28,'lt'],
            ['Fungicidas','FG008','Iprodione 50% WP','Control de Botrytis y Rhizoctonia en hortalizas',15.00,23.00,30,'kg'],
            ['Fungicidas','FG009','Azoxistrobín 250 SC','Estrobilurina de amplio espectro, preventivo y curativo',25.00,38.00,20,'lt'],
            ['Fungicidas','FG010','Trifloxistrobín 500 SC','Control de oidio, royas y manchas foliares',28.00,42.00,18,'lt'],

            // ── HERRAMIENTAS (11) ──────────────────────────────────────────
            ['Herramientas','H001','Azadón Forjado','Herramienta para deshierbe y preparación del suelo',5.00,8.00,20,'unidad'],
            ['Herramientas','H002','Pala Punta Cuadrada Forjada','Pala reforzada para trasplante y excavación',6.50,10.00,15,'unidad'],
            ['Herramientas','H003','Rastrillo Metálico 14 Dientes','Para nivelar y airear suelo, mango largo 1.4m',5.50,9.00,18,'unidad'],
            ['Herramientas','H004','Machete Bellota Curvo 18 pulgadas','Acero al carbono, mango de plástico ergonómico',8.00,13.00,25,'unidad'],
            ['Herramientas','H005','Bomba de Mochila Manual 20 L','Depósito polietileno, boquilla ajustable en cono y abanico',18.00,28.00,12,'unidad'],
            ['Herramientas','H006','Bomba Fumigadora Eléctrica 16 L','Batería 12V, presión constante, lanza telescópica',55.00,85.00,6,'unidad'],
            ['Herramientas','H007','Podadora Manual Telescópica','Alcance hasta 2.5m, trinquete de aluminio',22.00,34.00,8,'unidad'],
            ['Herramientas','H008','Manguera de Riego Reforzada 25m','3 capas, rosca 3/4, resistente al sol y presión',12.00,19.00,15,'unidad'],
            ['Herramientas','H009','Regadera Galvanizada 10 L','Con alcachofa desmontable, uso en jardín y huerto',9.00,14.50,12,'unidad'],
            ['Herramientas','H010','Carretilla de Jardín 100 L','Cuerpo de acero, rueda neumática, carga máx 150 kg',38.00,58.00,5,'unidad'],
            ['Herramientas','H011','Guantes de Nitrilo Jardinería','Par, talla M/L, resistente a cortes y humedad',2.50,4.50,50,'par'],

            // ── EQUIPOS DE RIEGO (10) ──────────────────────────────────────
            ['Equipos de Riego','ER001','Gotero Compensado 2 L/h','Riego preciso, compensación de presión 0.5-4 bar',0.25,0.50,500,'unidad'],
            ['Equipos de Riego','ER002','Aspersor Circular 360° 12 m','Radio de alcance 12m, boquilla antidrenaje',3.50,6.00,80,'unidad'],
            ['Equipos de Riego','ER003','Cinta de Goteo 16mm paso 30cm','Espesor 8 mil, caudal 1.2 L/h/gotero, rollo 500m',45.00,70.00,20,'rollo'],
            ['Equipos de Riego','ER004','Filtro de Malla 120 mesh 3/4 pulgada','Cuerpo plástico, malla inox, purga manual',5.50,9.00,40,'unidad'],
            ['Equipos de Riego','ER005','Válvula de Bola PVC 3/4 pulgada','Cierre hermético, rosca macho-hembra, PN10',2.00,3.50,60,'unidad'],
            ['Equipos de Riego','ER006','Timer de Riego Digital 4 Zonas','Programable, pantalla LCD, batería AA, IP44',28.00,44.00,10,'unidad'],
            ['Equipos de Riego','ER007','Tubería HDPE 32mm PN10 (rollo 100m)','Alta densidad, flexible, uso en riego a presión',38.00,58.00,12,'rollo'],
            ['Equipos de Riego','ER008','Racor Inicio de Cinta Goteo 16mm','Conector de sellado para cinta de goteo',0.15,0.35,400,'unidad'],
            ['Equipos de Riego','ER009','Tapón Final Cinta de Goteo 16mm','Cierre de extremo para cintas de riego',0.10,0.25,400,'unidad'],
            ['Equipos de Riego','ER010','Manifold 4 Salidas 3/4 pulgada','Distribuidor de agua para múltiples líneas de riego',7.00,11.50,25,'unidad'],

            // ── INSECTICIDAS (3) ───────────────────────────────────────────
            ['Insecticidas','IN001','Dimethoate 40% EC','Organofosforado sistémico para pulgones y moscas',7.50,12.00,40,'lt'],
            ['Insecticidas','IN002','Metomilo 90% SP','Carbamate de amplio espectro para lepidópteros',16.00,24.00,20,'kg'],
            ['Insecticidas','IN003','Clorfenapir 36% SC','Control de trips, ácaros y chinches resistentes',24.00,36.00,15,'lt'],
        ];

        // Contadores por categoría para ciclar imágenes
        $catCounters = [];

        foreach ($products as $p) {
            [$catName, $code, $name, $desc, $buy, $sell, $stock, $unit] = $p;

            if (! isset($cats[$catName])) {
                continue;
            }

            $catCounters[$catName] = $catCounters[$catName] ?? 0;
            $pool = $imgPool[$catName] ?? $imgPool['Fertilizantes'];
            $img  = $pool[$catCounters[$catName] % count($pool)];
            $catCounters[$catName]++;

            Product::updateOrCreate(
                ['code' => $code],
                [
                    'category_id'    => $cats[$catName],
                    'name'           => $name,
                    'description'    => $desc,
                    'purchase_price' => $buy,
                    'sale_price'     => $sell,
                    'stock'          => $stock,
                    'unit'           => $unit,
                    'image_url'      => $img,
                    'status'         => 'active',
                ]
            );
        }
    }
}
