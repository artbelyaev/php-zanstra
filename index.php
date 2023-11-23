<?php
interface Chargeable {
    public function getPrice(): int|float;
}

class ShopProduct implements Chargeable {
    private int $discount = 0;
    private int $id = 0;
    
    public function __construct(
        private string $title, 
        private string $producerFirstName,
        private string $producerMainName, 
        protected int|float $price)
    {
    }

    public function getProducerFirstName(): string {
        return $this->producerFirstName;
    }

    public function getProducerMainName(): string {
        return $this->producerMainName;
    }

    public function setDiscount(int $num): void {
        $this->discount = $num;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getDiscount(): int {
        return $this->discount;
    }

    public function getTitle() : string {
        return $this->title;
    }

    public function getPrice(): int|float {
        return ($this->price - (($this->discount * $this->price) / 100));
    }

    public function getProducer(): string {
        return $this->producerFirstName . ' ' . $this->producerMainName;
    }

    public function getSummaryLine(): string {
        return "{$this->title} ({$this->producerMainName}, {$this->producerFirstName})";
    }

    public static function getInstance(int $id, \PDO $pdo): ShopProduct {
        $stmt = $pdo->prepare('select * from products where id=?');
        $result = $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (empty($row)) {
            return null;
        }

        if ($row['type'] === 'book') {
            $product = new BookProduct($row['title'], $row['firstname'], $row['mainname'], (float) $row['price'], (int) $row['numpages']);
        } elseif ($row['type' === 'cd']) {
            $product = new CDProduct($row['title'], $row['firstname'], $row['mainname'], (float) $row['price'], (int) $row['playlength']);
        } else {
            $firstname = (is_null($row['firstname'])) ? '' : $row['firstname'];
            $product = new ShopProduct($row['title'], $firstname, $row['mainname'], (float) $row['price']);
        }

        $product->setId((int) $row['id']);
        $product->setDiscount((int) $row['discount']);
        return $product;
    }
    
}

class BookProduct extends ShopProduct {
    public $numPages;

    public function __construct(string $title, string $firstName, string $mainName, float $price, int $numPages)
    {
        parent::__construct($title, $firstName, $mainName, $price);
        $this->numPages = $numPages;
    }

    public function getNumPages(): int {
        return $this->numPages;
    }

    public function getSummaryLine(): string {
        $str = parent::getSummaryLine();
        $str .= ": {$this->numPages} стр.";

        return $str;
    }

    public function getPrice(): int|float {
        return $this->price;
    }
}

class CDProduct extends ShopProduct {

    public function __construct(string $title, string $firstName, string $mainName, int|float $price, private int $playLength)
    {
        parent::__construct($title, $firstName, $mainName, $price);
    }

    public function getPlayLength(): int {
        return $this->playLength;
    }

    public function getSummaryLine(): string {
        $str = parent::getSummaryLine();
        $str .= ": Время звучания - {$this->playLength}";

        return $str;
    }
}

abstract class ShopProductWriter {
    protected array $products = [];

    public function addProduct(ShopProduct $shopProduct): void {
        $this->products[] = $shopProduct;
    }

    abstract public function write(): void; 
}

class XmlProductWriter extends ShopProductWriter {
    public function write(): void
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('Товары');

        foreach($this->products as $shopProduct) {
            $writer->startElement('Товар');
            $writer->writeAttribute('Наименование', $shopProduct->getTitle());
            $writer->startElement('Резюме');
            $writer->text($shopProduct->getSummaryLine());
            $writer->endElement();
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();
        print $writer->flush();
    }
}

class TextProductWriter extends ShopProductWriter {
    public function write(): void
    {
        $str = "ТОВАРЫ:\n";
        foreach($this->products as $shopProduct) {
            $str .= $shopProduct->getSummaryLine() . "\n";
        }

        print $str;
    }
}

$bookl = new ShopProduct('Собачье сердце', 'Михаил', 'Булгаков', 5.99);
$writer = new ShopProductWriter();
$writer->addProduct($bookl);

$writer->write();

$db = new SQLite3('test.db');
$sql = 'CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT,
    firstname TEXT,
    mainname TEXT,
    title TEXT,
    price float,
    numpages int,
    playlength int,
    discount int )';

$db->query($sql);

var_dump($db);

?>