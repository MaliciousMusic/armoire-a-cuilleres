<?php
/* Contenu de départ (importé une seule fois, à la création de la base ; ensuite tout se modifie dans /admin).
   Sources : carte 2026 de la boutique, ardoise du 17 sept. 2026, fiche Google (oct. 2026), Instagram.
   Chaque texte : ['fr' => …, 'en' => …, 'de' => …, 'es' => …, 'zh' => …] ; une langue absente retombe sur le français.
   p = prix en centimes ; tags : bio, vegan, froid, nouveau, signature, extra (supplément « + »). */
return [
    'menu' => [
        [
            't' => ['fr' => 'Chocolats chauds grands crus', 'en' => 'Single-origin hot chocolates', 'de' => 'Grand-Cru-Trinkschokoladen', 'es' => 'Chocolates calientes grand cru', 'zh' => '精品热巧克力'],
            'note' => ['fr' => 'Préparés à la minute avec du lait bio d’Auvergne, servis autour de 65 °C : jamais bouillants.', 'en' => 'Made to order with organic Auvergne milk and served at about 65 °C, never scalding.', 'de' => 'Frisch zubereitet mit Bio-Milch aus der Auvergne und mit etwa 65 °C serviert: nie kochend heiß.', 'es' => 'Preparados al momento con leche ecológica de Auvernia y servidos a unos 65 °C: nunca hirviendo.', 'zh' => '选用奥弗涅有机牛奶现点现做，约65 °C上桌，绝不烫口。'],
            'p' => 520,
            'items' => [
                ['n' => ['fr' => 'Blanc Ivoire 33 %'], 'd' => ['fr' => 'Chocolat blanc peu sucré, notes de gâteau au beurre et de vanille.', 'en' => 'Lightly sweet white chocolate with notes of butter cake and vanilla.', 'de' => 'Wenig süße weiße Schokolade mit Noten von Butterkuchen und Vanille.', 'es' => 'Chocolate blanco poco dulce, con notas de bizcocho de mantequilla y vainilla.', 'zh' => '低甜度白巧克力，带黄油蛋糕和香草风味。']],
                ['n' => ['fr' => 'Kewane Lait 34 %'], 'd' => ['fr' => 'Crémeux et réglissé.', 'en' => 'Creamy, with a hint of liquorice.', 'de' => 'Cremig, mit einem Hauch Lakritz.', 'es' => 'Cremoso, con un toque de regaliz.', 'zh' => '口感顺滑，带甘草风味。']],
                ['n' => ['fr' => 'Caramélia 35 %'], 'd' => ['fr' => 'Douceur sucrée et ronde.', 'en' => 'Round, sweet and mellow.', 'de' => 'Rund, süß und mild.', 'es' => 'Dulce, redondo y suave.', 'zh' => '香甜圆润。']],
                ['n' => ['fr' => 'Elianza Lait 35 %'], 'd' => ['fr' => 'Crème glacée, caramel au beurre, cacao grillé et miel de châtaignier.', 'en' => 'Ice cream, butter caramel, roasted cocoa and chestnut honey.', 'de' => 'Speiseeis, Butterkaramell, gerösteter Kakao und Kastanienhonig.', 'es' => 'Helado, caramelo de mantequilla, cacao tostado y miel de castaño.', 'zh' => '冰淇淋、黄油焦糖、烘烤可可和栗子花蜜风味。']],
                ['n' => ['fr' => 'Vanuari Lait 39 %'], 'd' => ['fr' => 'Lait entier aux notes sucrées.', 'en' => 'Whole milk with sweet notes.', 'de' => 'Vollmilch mit süßen Noten.', 'es' => 'Leche entera con notas dulces.', 'zh' => '全脂牛奶，带香甜风味。']],
                ['n' => ['fr' => 'Z-Caramel 43 %'], 'd' => ['fr' => 'Une texture craquante de caramel.', 'en' => 'Crunchy caramel texture.', 'de' => 'Knusprige Karamelltextur.', 'es' => 'Una textura crujiente de caramelo.', 'zh' => '酥脆的焦糖口感。']],
                ['n' => ['fr' => 'Elianza Noir 55 %'], 'd' => ['fr' => 'Cacao grillé, réglisse, anis étoilé et vanille.', 'en' => 'Roasted cocoa, liquorice, star anise and vanilla.', 'de' => 'Gerösteter Kakao, Lakritz, Sternanis und Vanille.', 'es' => 'Cacao tostado, regaliz, anís estrellado y vainilla.', 'zh' => '烘烤可可、甘草、八角和香草。']],
                ['n' => ['fr' => 'Vanuari Noir 63 %'], 'd' => ['fr' => 'Fluide et équilibré : douceur du miel, fruits rouges et poivre.', 'en' => 'Smooth and balanced: honey, red berries and pepper.', 'de' => 'Fließend und ausgewogen: Honigsüße, rote Beeren und Pfeffer.', 'es' => 'Fluido y equilibrado: dulzor de miel, frutos rojos y pimienta.', 'zh' => '顺滑均衡：蜂蜜的甘甜、红莓果和胡椒风味。']],
                ['n' => ['fr' => 'Z-Café 70 %'], 'd' => ['fr' => 'De vrais grains de café broyés avec le cacao.', 'en' => 'Real coffee beans ground with the cocoa.', 'de' => 'Echte Kaffeebohnen, zusammen mit dem Kakao gemahlen.', 'es' => 'Auténticos granos de café molidos con el cacao.', 'zh' => '真正的咖啡豆与可可一同研磨。']],
                ['n' => ['fr' => 'Mokaya Noir 75 %'], 'd' => ['fr' => 'Fruits exotiques, miel, amandes et noisettes grillées, vanille.', 'en' => 'Tropical fruit, honey, roasted almonds and hazelnuts, vanilla.', 'de' => 'Exotische Früchte, Honig, geröstete Mandeln und Haselnüsse, Vanille.', 'es' => 'Frutas tropicales, miel, almendras y avellanas tostadas, vainilla.', 'zh' => '热带水果、蜂蜜、烤杏仁和烤榛子、香草。'], 'tags' => 'bio'],
                ['n' => ['fr' => 'Arcango Noir 85 %'], 'd' => ['fr' => 'Cacao grillé aux légères notes de châtaigne.', 'en' => 'Roasted cocoa with light chestnut notes.', 'de' => 'Gerösteter Kakao mit leichten Kastaniennoten.', 'es' => 'Cacao tostado con ligeras notas de castaña.', 'zh' => '烘烤可可，带淡淡栗子香。']],
                ['n' => ['fr' => 'Noir Infini 99 %'], 'd' => ['fr' => 'La saveur la plus intense.', 'en' => 'The most intense of all.', 'de' => 'Der intensivste Geschmack von allen.', 'es' => 'El sabor más intenso.', 'zh' => '风味最浓郁的一款。'], 'tags' => 'signature'],
            ],
        ],
        [
            't' => ['fr' => 'Les classiques', 'en' => 'The classics', 'de' => 'Die Klassiker', 'es' => 'Los clásicos', 'zh' => '经典款'],
            'items' => [
                ['n' => ['fr' => 'Chocolat Soyeux', 'en' => 'Silky hot chocolate', 'de' => 'Seidige heiße Schokolade', 'es' => 'Chocolate sedoso', 'zh' => '丝滑热巧克力'], 'd' => ['fr' => 'Doux et non sucré.', 'en' => 'Mild and unsweetened.', 'de' => 'Mild und ungesüßt.', 'es' => 'Suave y sin azúcar.', 'zh' => '口感柔和，不加糖。'], 'p' => 470],
                ['n' => ['fr' => 'Chocolat Intense', 'en' => 'Intense hot chocolate', 'de' => 'Kräftige heiße Schokolade', 'es' => 'Chocolate intenso', 'zh' => '浓郁热巧克力'], 'd' => ['fr' => 'Épais et corsé, cannelle et miel.', 'en' => 'Thick and bold, with cinnamon and honey.', 'de' => 'Dickflüssig und kräftig, mit Zimt und Honig.', 'es' => 'Espeso y con cuerpo, con canela y miel.', 'zh' => '浓稠醇厚，带肉桂和蜂蜜风味。'], 'p' => 520, 'tags' => 'signature'],
            ],
        ],
        [
            't' => ['fr' => 'Chocolats gourmands', 'en' => 'Indulgent hot chocolates', 'de' => 'Schokoladen zum Schlemmen', 'es' => 'Chocolates golosos', 'zh' => '风味热巧克力'],
            'items' => [
                ['n' => ['fr' => 'Praliné noisette', 'en' => 'Hazelnut praline', 'de' => 'Haselnuss-Praliné', 'es' => 'Praliné de avellana', 'zh' => '榛子果仁糖'], 'p' => 590],
                ['n' => ['fr' => 'Guimauve', 'en' => 'Marshmallow', 'de' => 'Marshmallow', 'es' => 'Malvavisco', 'zh' => '棉花糖'], 'p' => 590],
                ['n' => ['fr' => 'Coco façon Bounty', 'en' => 'Coconut, Bounty-style', 'de' => 'Kokos à la Bounty', 'es' => 'Coco estilo Bounty', 'zh' => 'Bounty 风味椰子'], 'p' => 590],
            ],
        ],
        [
            't' => ['fr' => 'Chocolats glacés', 'en' => 'Iced chocolates', 'de' => 'Eiskalte Schokoladen', 'es' => 'Chocolates fríos', 'zh' => '冰巧克力'],
            'items' => [
                ['n' => ['fr' => 'Chocolat frappé', 'en' => 'Chocolate frappé', 'de' => 'Schokoladen-Frappé', 'es' => 'Chocolate frappé', 'zh' => '巧克力冰沙'], 'p' => 550, 'tags' => 'froid'],
                ['n' => ['fr' => 'Mocha glacé', 'en' => 'Iced mocha', 'de' => 'Eis-Mocha', 'es' => 'Mocha frío', 'zh' => '冰摩卡'], 'p' => 580, 'tags' => 'froid'],
            ],
        ],
        [
            't' => ['fr' => 'Cafés', 'en' => 'Coffee', 'de' => 'Kaffee', 'es' => 'Cafés', 'zh' => '咖啡'],
            'note' => ['fr' => 'Café torréfié par Chazal, à Issoire.', 'en' => 'Coffee roasted by Chazal in Issoire.', 'de' => 'Kaffee aus der Rösterei Chazal in Issoire.', 'es' => 'Café tostado por Chazal, en Issoire.', 'zh' => '咖啡豆由伊苏瓦尔（Issoire）的 Chazal 烘焙。'],
            'items' => [
                ['n' => ['fr' => 'Espresso', 'zh' => '意式浓缩'], 'p' => 160],
                ['n' => ['fr' => 'Lungo', 'zh' => '意式长萃'], 'p' => 180],
                ['n' => ['fr' => 'Café viennois', 'en' => 'Viennese coffee', 'de' => 'Wiener Kaffee mit Sahne', 'es' => 'Café vienés', 'zh' => '维也纳咖啡'], 'p' => 250],
                ['n' => ['fr' => 'Cappuccino', 'zh' => '卡布奇诺'], 'p' => 390],
                ['n' => ['fr' => 'Café latte', 'en' => 'Caffè latte', 'de' => 'Caffè Latte', 'es' => 'Café con leche', 'zh' => '拿铁'], 'p' => 450],
                ['n' => ['fr' => 'Mocha', 'zh' => '摩卡'], 'd' => ['fr' => 'Espresso, chocolat et lait.', 'en' => 'Espresso, chocolate and milk.', 'de' => 'Espresso, Schokolade und Milch.', 'es' => 'Espresso, chocolate y leche.', 'zh' => '意式浓缩、巧克力和牛奶。'], 'p' => 450],
            ],
        ],
        [
            't' => ['fr' => 'Spécialités', 'en' => 'Specialities', 'de' => 'Spezialitäten', 'es' => 'Especialidades', 'zh' => '特色饮品'],
            'items' => [
                ['n' => ['fr' => 'Chaï latte', 'zh' => '香料茶拿铁'], 'd' => ['fr' => 'Latte aux épices.', 'en' => 'Spiced latte.', 'de' => 'Latte mit Gewürzen.', 'es' => 'Latte especiado.', 'zh' => '香料奶茶。'], 'p' => 450],
                ['n' => ['fr' => 'Matcha latte', 'zh' => '抹茶拿铁'], 'p' => 550],
                ['n' => ['fr' => 'Matcha fraise', 'en' => 'Strawberry matcha', 'de' => 'Erdbeer-Matcha', 'es' => 'Matcha con fresa', 'zh' => '草莓抹茶'], 'p' => 600, 'tags' => 'nouveau'],
            ],
        ],
        [
            't' => ['fr' => 'Thés & infusions', 'en' => 'Teas & infusions', 'de' => 'Tees & Kräutertees', 'es' => 'Tés e infusiones', 'zh' => '茶与花草茶'],
            'p' => 390,
            'items' => [
                ['n' => ['fr' => 'Montagne Bleue'], 'd' => ['fr' => 'Thé noir, fraise et rhubarbe.', 'en' => 'Black tea, strawberry and rhubarb.', 'de' => 'Schwarzer Tee, Erdbeere und Rhabarber.', 'es' => 'Té negro, fresa y ruibarbo.', 'zh' => '红茶，草莓和大黄。']],
                ['n' => ['fr' => 'Blue of London'], 'd' => ['fr' => 'Thé noir du Yunnan et bergamote.', 'en' => 'Yunnan black tea and bergamot.', 'de' => 'Schwarzer Yunnan-Tee und Bergamotte.', 'es' => 'Té negro de Yunnan y bergamota.', 'zh' => '云南红茶与佛手柑。']],
                ['n' => ['fr' => 'Thé des Alizés'], 'd' => ['fr' => 'Thé vert, pêche blanche et fleur d’oranger.', 'en' => 'Green tea, white peach and orange blossom.', 'de' => 'Grüner Tee, weißer Pfirsich und Orangenblüte.', 'es' => 'Té verde, melocotón blanco y azahar.', 'zh' => '绿茶，白桃和橙花。']],
                ['n' => ['fr' => 'Thé des Fakirs'], 'd' => ['fr' => 'Thé vert, cardamome, gingembre et agrumes.', 'en' => 'Green tea, cardamom, ginger and citrus.', 'de' => 'Grüner Tee, Kardamom, Ingwer und Zitrusfrüchte.', 'es' => 'Té verde, cardamomo, jengibre y cítricos.', 'zh' => '绿茶，小豆蔻、生姜和柑橘。']],
                ['n' => ['fr' => 'Thé Merveilleux'], 'd' => ['fr' => 'Thé vert, amandes caramélisées et pistache.', 'en' => 'Green tea, caramelised almonds and pistachio.', 'de' => 'Grüner Tee, karamellisierte Mandeln und Pistazie.', 'es' => 'Té verde, almendras caramelizadas y pistacho.', 'zh' => '绿茶，焦糖杏仁和开心果。']],
                ['n' => ['fr' => 'Limoncha'], 'd' => ['fr' => 'Thés vert et noir d’inspiration japonaise, riz grillé, amande et citron.', 'en' => 'Japanese-style green and black teas, toasted rice, almond and lemon.', 'de' => 'Grüner und schwarzer Tee nach japanischer Art, gerösteter Reis, Mandel und Zitrone.', 'es' => 'Tés verde y negro de inspiración japonesa, arroz tostado, almendra y limón.', 'zh' => '日式风格绿茶与红茶，玄米、杏仁和柠檬。']],
                ['n' => ['fr' => 'Mama Relax'], 'd' => ['fr' => 'Infusion bio, mélisse et lavande.', 'en' => 'Organic infusion, lemon balm and lavender.', 'de' => 'Bio-Kräutertee, Zitronenmelisse und Lavendel.', 'es' => 'Infusión ecológica, melisa y lavanda.', 'zh' => '有机花草茶，香蜂草和薰衣草。'], 'tags' => 'bio'],
                ['n' => ['fr' => 'L’Herboriste'], 'd' => ['fr' => 'Infusion bio, curcuma, réglisse et verveine.', 'en' => 'Organic infusion, turmeric, liquorice and verbena.', 'de' => 'Bio-Kräutertee, Kurkuma, Süßholz und Verbene.', 'es' => 'Infusión ecológica, cúrcuma, regaliz y verbena.', 'zh' => '有机花草茶，姜黄、甘草和马鞭草。'], 'tags' => 'bio'],
                ['n' => ['fr' => 'Paris for You'], 'd' => ['fr' => 'Thé vert, litchi, framboise et notes de rose.', 'en' => 'Green tea, lychee, raspberry and rose.', 'de' => 'Grüner Tee, Litschi, Himbeere und Rosennoten.', 'es' => 'Té verde, lichi, frambuesa y notas de rosa.', 'zh' => '绿茶，荔枝、覆盆子和玫瑰香。']],
                ['n' => ['fr' => 'Louvre côté Jardin'], 'd' => ['fr' => 'Thé vert, pomme, prune et coing.', 'en' => 'Green tea, apple, plum and quince.', 'de' => 'Grüner Tee, Apfel, Pflaume und Quitte.', 'es' => 'Té verde, manzana, ciruela y membrillo.', 'zh' => '绿茶，苹果、李子和榅桲。']],
            ],
        ],
        [
            't' => ['fr' => 'Boissons fraîches', 'en' => 'Cold drinks', 'de' => 'Kalte Getränke', 'es' => 'Bebidas frías', 'zh' => '冷饮'],
            'items' => [
                ['n' => ['fr' => 'Matcha glacé', 'en' => 'Iced matcha', 'de' => 'Eis-Matcha', 'es' => 'Matcha frío', 'zh' => '冰抹茶'], 'p' => 550, 'tags' => 'froid'],
                ['n' => ['fr' => 'Citronnade maison', 'en' => 'Homemade lemonade', 'de' => 'Hausgemachte Zitronenlimonade', 'es' => 'Limonada casera', 'zh' => '自制柠檬水'], 'p' => 450, 'tags' => 'froid'],
                ['n' => ['fr' => 'Thé glacé maison', 'en' => 'Homemade iced tea', 'de' => 'Hausgemachter Eistee', 'es' => 'Té helado casero', 'zh' => '自制冰茶'], 'd' => ['fr' => 'Pêche, sureau, framboise ou citron.', 'en' => 'Peach, elderflower, raspberry or lemon.', 'de' => 'Pfirsich, Holunderblüte, Himbeere oder Zitrone.', 'es' => 'Melocotón, flor de saúco, frambuesa o limón.', 'zh' => '蜜桃、接骨木花、覆盆子或柠檬口味。'], 'p' => 400, 'tags' => 'froid'],
                ['n' => ['fr' => 'Sodas', 'en' => 'Soft drinks', 'de' => 'Softdrinks', 'es' => 'Refrescos', 'zh' => '汽水'], 'd' => ['fr' => 'Coca-Cola, Coca-Cola Zéro ou limonade Lorina.', 'en' => 'Coca-Cola, Coca-Cola Zero or Lorina lemonade.', 'de' => 'Coca-Cola, Coca-Cola Zero oder Lorina-Limonade.', 'es' => 'Coca-Cola, Coca-Cola Zero o limonada Lorina.', 'zh' => '可口可乐、零度可口可乐或 Lorina 柠檬汽水。'], 'p' => 400, 'tags' => 'froid'],
                ['n' => ['fr' => 'Jus de fruits', 'en' => 'Fruit juice', 'de' => 'Fruchtsaft', 'es' => 'Zumo de frutas', 'zh' => '果汁'], 'p' => 400, 'tags' => 'froid'],
                ['n' => ['fr' => 'Sirop à l’eau', 'en' => 'Cordial', 'de' => 'Sirup mit Wasser', 'es' => 'Sirope con agua', 'zh' => '果味糖浆水'], 'p' => 200, 'tags' => 'froid'],
            ],
        ],
        [
            't' => ['fr' => 'Pâtisseries maison', 'en' => 'Homemade cakes', 'de' => 'Hausgemachte Kuchen', 'es' => 'Repostería casera', 'zh' => '手工甜点'],
            'note' => ['fr' => 'Les gâteaux du jour sont sur l’ardoise : ils changent chaque jour.', 'en' => 'The cakes of the day are on the chalkboard and change daily.', 'de' => 'Die Kuchen des Tages stehen auf der Kreidetafel: Sie wechseln jeden Tag.', 'es' => 'Los dulces del día están en la pizarra: cambian cada día.', 'zh' => '每日甜点写在小黑板上，每天都不一样。'],
            'items' => [
                ['n' => ['fr' => 'Fondant chocolat noir', 'en' => 'Dark chocolate fondant', 'de' => 'Zartbitter-Schokoladenfondant', 'es' => 'Fondant de chocolate negro', 'zh' => '黑巧克力软心蛋糕'], 'd' => ['fr' => 'Pointe de fleur de sel.', 'en' => 'With a pinch of sea salt.', 'de' => 'Mit einer Prise Fleur de Sel.', 'es' => 'Con un toque de flor de sal.', 'zh' => '点缀少许海盐。'], 'p' => 550, 'tags' => 'signature'],
                ['n' => ['fr' => 'Fondant au lait caramélisé', 'en' => 'Caramelised milk chocolate fondant', 'de' => 'Fondant aus karamellisierter Milchschokolade', 'es' => 'Fondant de chocolate con leche caramelizado', 'zh' => '焦糖牛奶巧克力软心蛋糕'], 'p' => 550],
                ['n' => ['fr' => 'Brownie noix et noisettes', 'en' => 'Walnut & hazelnut brownie', 'de' => 'Brownie mit Walnüssen und Haselnüssen', 'es' => 'Brownie de nueces y avellanas', 'zh' => '核桃榛子布朗尼'], 'p' => 550],
                ['n' => ['fr' => 'Cheesecake citron vert', 'en' => 'Lime cheesecake', 'de' => 'Limetten-Käsekuchen', 'es' => 'Tarta de queso con lima', 'zh' => '青柠芝士蛋糕'], 'p' => 550],
                ['n' => ['fr' => 'Tarte citron meringuée', 'en' => 'Lemon meringue tart', 'de' => 'Zitronentarte mit Baiser', 'es' => 'Tarta de limón y merengue', 'zh' => '柠檬蛋白霜挞'], 'p' => 520],
                ['n' => ['fr' => 'Cake marbré chocolat', 'en' => 'Chocolate marble cake', 'de' => 'Schokoladen-Marmorkuchen', 'es' => 'Bizcocho marmolado de chocolate', 'zh' => '巧克力大理石磅蛋糕'], 'p' => 490],
                ['n' => ['fr' => 'Cookie pépites de chocolat', 'en' => 'Chocolate chip cookie', 'de' => 'Cookie mit Schokostückchen', 'es' => 'Cookie con pepitas de chocolate', 'zh' => '巧克力豆曲奇'], 'p' => 390],
            ],
        ],
        [
            't' => ['fr' => 'Petite faim', 'en' => 'Feeling peckish', 'de' => 'Kleiner Hunger', 'es' => 'Para un hambre pequeña', 'zh' => '小小饿意'],
            'items' => [
                ['n' => ['fr' => 'Formule enfant', 'en' => 'Kids’ set', 'de' => 'Kindermenü', 'es' => 'Menú infantil', 'zh' => '儿童套餐'], 'd' => ['fr' => 'Lait spéculoos-vanille, chocolat chaud ou boisson fraîche, et une petite part de gâteau.', 'en' => 'Speculoos-vanilla milk, hot chocolate or a cold drink, plus a small slice of cake.', 'de' => 'Spekulatius-Vanille-Milch, heiße Schokolade oder ein kaltes Getränk, dazu ein kleines Stück Kuchen.', 'es' => 'Leche de speculoos y vainilla, chocolate caliente o bebida fría, y una porción pequeña de tarta.', 'zh' => '焦糖饼干香草牛奶、热巧克力或冷饮任选一款，再配一小块蛋糕。'], 'p' => 560],
            ],
        ],
        [
            't' => ['fr' => 'Suppléments', 'en' => 'Extras', 'de' => 'Extras', 'es' => 'Suplementos', 'zh' => '加料'],
            'items' => [
                ['n' => ['fr' => 'Arôme', 'en' => 'Syrup', 'de' => 'Sirup', 'es' => 'Sirope', 'zh' => '风味糖浆'], 'd' => ['fr' => 'Vanille, caramel, spéculoos ou praliné noisette.', 'en' => 'Vanilla, caramel, speculoos or hazelnut praline.', 'de' => 'Vanille, Karamell, Spekulatius oder Haselnuss-Praliné.', 'es' => 'Vainilla, caramelo, speculoos o praliné de avellana.', 'zh' => '香草、焦糖、焦糖饼干或榛子果仁糖。'], 'p' => 30, 'tags' => 'extra'],
                ['n' => ['fr' => 'Lait végétal', 'en' => 'Plant milk', 'de' => 'Pflanzenmilch', 'es' => 'Leche vegetal', 'zh' => '植物奶'], 'd' => ['fr' => 'Avoine, coco ou amande.', 'en' => 'Oat, coconut or almond.', 'de' => 'Hafer, Kokos oder Mandel.', 'es' => 'Avena, coco o almendra.', 'zh' => '燕麦奶、椰奶或杏仁奶。'], 'p' => 50, 'tags' => 'extra,vegan'],
                ['n' => ['fr' => 'Chantilly', 'en' => 'Whipped cream', 'de' => 'Schlagsahne', 'es' => 'Nata montada', 'zh' => '鲜奶油'], 'p' => 50, 'tags' => 'extra'],
            ],
        ],
    ],

    'team' => [
        ['name' => '[Prénom]', 'tint' => 'cacao',
            'role' => ['fr' => 'Gérant·e', 'en' => 'Manager', 'de' => 'Inhaber·in', 'es' => 'Gerente', 'zh' => '店主'],
            'bio' => ['fr' => '[Quelques mots sur cette personne, à compléter dans l’espace équipe.]', 'en' => '[A few words about this person, to be filled in from the staff area.]', 'de' => '[Ein paar Worte über diese Person, im Mitarbeiterbereich zu ergänzen.]', 'es' => '[Unas palabras sobre esta persona, para completar en el espacio del equipo.]', 'zh' => '[关于此人的简短介绍，请在员工后台填写。]']],
        ['name' => '[Prénom]', 'tint' => 'rose',
            'role' => ['fr' => 'Pâtissier·ère', 'en' => 'Pastry chef', 'de' => 'Konditor·in', 'es' => 'Pastelero·a', 'zh' => '甜点师'],
            'bio' => ['fr' => '[Quelques mots sur cette personne, à compléter dans l’espace équipe.]', 'en' => '[A few words about this person, to be filled in from the staff area.]', 'de' => '[Ein paar Worte über diese Person, im Mitarbeiterbereich zu ergänzen.]', 'es' => '[Unas palabras sobre esta persona, para completar en el espacio del equipo.]', 'zh' => '[关于此人的简短介绍，请在员工后台填写。]']],
        ['name' => 'L’équipe du salon', 'tint' => 'sauge',
            'role' => ['fr' => 'Au service', 'en' => 'Front of house', 'de' => 'Service', 'es' => 'En sala', 'zh' => '店内服务'],
            'bio' => ['fr' => 'Ils vous conseillent un cru, montent la chantilly et connaissent par cœur les thés de l’armoire. Photo à venir !', 'en' => 'They’ll help you choose a single origin, whip the cream and know every tea in the cupboard by heart. Photo coming soon!', 'de' => 'Sie empfehlen Ihnen einen Grand Cru, schlagen die Sahne und kennen alle Tees im Schrank auswendig. Foto folgt!', 'es' => 'Te recomiendan un grand cru, montan la nata y se saben de memoria todos los tés del armario. ¡Foto próximamente!', 'zh' => '他们会为您推荐一款精品热巧克力，亲手打发鲜奶油，对柜子里的每一款茶都了如指掌。照片即将上线！']],
    ],
];
