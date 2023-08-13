<?
class TAdminStub extends TInterface
{
    public $MODE;
    public $DATA;
    public $HEAD;

    public function __construct()
    {
        parent::__construct();
        $this->HEAD = "Админ панель";
        $this->DATA = "";
    }
}
?>
