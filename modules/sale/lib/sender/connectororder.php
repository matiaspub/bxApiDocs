<?

namespace Bitrix\Sale\Sender;


class ConnectorOrder extends \Bitrix\Sender\Connector
{
    static public function getName()
    {
        return 'Sale - orders';
    }

    static public function getCode()
    {
        return "order";
    }


    /** @return \CDBResult */
    public function getData()
    {
        $runtime = array();
        $filter = array();

        if($this->getFieldValue('LID'))
            $filter['=LID'] = $this->getFieldValue('LID', null);

        if($this->getFieldValue('ID'))
            $filter['=ID'] = $this->getFieldValue('ID', 0);

        if($this->getFieldValue('USER_ID'))
            $filter['=USER_ID'] = $this->getFieldValue('USER_ID', 0);

        if($this->getFieldValue('BASKET_PRODUCT_ID'))
        {
            $filter['=BASKET.PRODUCT_ID'] = $this->getFieldValue('BASKET_PRODUCT_ID', 0);
            $runtime['BASKET'] = array(
                'data_type' => 'Bitrix\Sale\Internals\Basket',
                'reference' => array(
                    '=this.ID' => 'ref.ORDER_ID'
                )
            );
        }

        $resultDb = \Bitrix\Sale\Internals\OrderTable::getList(array(
            'select' => array('USER_ID', 'NAME' => 'USER.NAME', 'EMAIL' => 'USER.EMAIL'),
            'filter' => $filter,
            'runtime' => $runtime,
            'group' => array('USER_ID', 'NAME', 'EMAIL'),
            'order' => array('USER_ID' => 'ASC'),
        ));

        return new \CDBResult($resultDb);
    }

    static public function getForm()
    {
        return '';
    }

}