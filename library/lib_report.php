<?
class TReport extends TInterface
{
    private $TPL;
    public $MODE;

    public function __construct()
    {
        parent::__construct();
        $this->TPL = _TEMPLATE."report/";
        $this->MODE = SafeStr(@$_REQUEST["report"]);
    }

    public function StickerInit($orientation)
    {
        include(_ENGINE."pdf/fpdf.php");
        include(_ENGINE."pdf/pdf.php");

        $pdf = new PDF($orientation);
        $pdf->AddPage();
        $pdf->AddFont('nina');
        $pdf->SetFont('nina');

        return $pdf;
    }

    public function StickerAnnounce()
    {
        $id = SafeInt(@$_REQUEST["id"]);
        // Поиск объявления
        $SQL = "select ID_ANNOUNCE, ID_STATE, COST, ID_CURRENCY, CAPTION, uncompress(textdata) TEXTDATA, uncompress(CONTACT) CONTACT from ANNOUNCE_DATA where id_announce=".$id;
        $item = $this->DL->LFetchRecord($SQL) or die();
        // Контакты и наименование
        $contact = parent::ContactViewNative($item["CONTACT"], 1);
        $caption = utf_convert(htmlspecialchars_decode(($item["CAPTION"])));
        // Текст
        $textdata = ShortString(htmlspecialchars_decode(BBCodeToPlain($item["TEXTDATA"])), 500)." Контакты: ";
        $textdata .= parent::ContactViewNative($item["CONTACT"], 30, " ");
        $textdata = utf_convert($textdata);
        // Разбивка и картинки
        $sizeTriple = 98;
        $imageAnnounce = parent::GetAnnounceImage($item["ID_ANNOUNCE"], $item["ID_STATE"]);
        $imageLogotype = "images/logotype.png";

        // Инициализация
        $pdf = self::StickerInit("P");
        // В три строки
        for ($height = 0; $height < 3; $height++)
        {
            // Заголовок с линией
            $pdf->SetFontSize(16);
            $pdf->Text(10, $height * $sizeTriple + 10, $caption);
            $pdf->SetLineWidth(1);
            $pdf->Line(10, $height * $sizeTriple + 12, 200, $height * $sizeTriple + 12);
            // Текст
            $pdf->SetLineWidth(0.1);
            $pdf->SetFontSize(11);
            $pdf->SetXY(51, $height * $sizeTriple + 15);
            $pdf->MultiCell(150, 5, $textdata, 0, "L");
            // Верхняя и нижняя линия отрыва
            $pdf->Line(10, ($height + 1) * $sizeTriple, 200, ($height + 1) * $sizeTriple);
            $pdf->Line(10, ($height + 1) * $sizeTriple - 40, 200, ($height + 1) * $sizeTriple - 40);
            // Изображение и логотип
            $pdf->Image($imageAnnounce, 10, $height * $sizeTriple + 15, 40, 40);
            $pdf->Image($imageLogotype, 177, $height * $sizeTriple + 4, 24, 8);
            // Отрывные талончики
            for ($width = 0; $width < 9; $width++)
            {
                // Наименование
                $pdf->SetFontSize(10);
                $pdf->SetXY(21 * $width + 30, ($height + 1) * $sizeTriple - 39);
                $pdf->Rotate(270);
                $pdf->MultiCell(40, 3, $caption, 0, "C");
                // Контакты
                $pdf->SetXY(21 * $width + 19, ($height + 1) * $sizeTriple - 39);
                $pdf->Rotate(270);
                $pdf->SetFontSize(10);
                $pdf->MultiCell(40, 4, $contact, 0, "C");
                // Забор
                $pdf->Rotate(0);
                $pdf->Line(21 * $width + 10, ($height + 1) * $sizeTriple, 21 * $width + 10, ($height + 1) * $sizeTriple - 40);
                // Профит
                $pdf->SetFontSize(8);
                $pdf->Text(21 * $width + 13, ($height + 1) * $sizeTriple - 38, 'www.'.$_SERVER["HTTP_HOST"]);
            }
            // Закрыть забор
            $pdf->Line(21 * 9 + 11, ($height + 1) * $sizeTriple, 21 * 9 + 11, ($height + 1) * $sizeTriple - 40);
        }
        $pdf->Output();
    }
}
?>