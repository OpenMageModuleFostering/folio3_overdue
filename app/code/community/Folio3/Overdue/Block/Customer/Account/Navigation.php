<?php
    class Folio3_Overdue_Block_Customer_Account_Navigation extends Mage_Customer_Block_Account_Navigation{
        /**
         * Removes link by url
         *
         * @param string $url
         * @return Mage_Page_Block_Template_Links
         */
        public function removeLinkByUrl($url)
        {
            foreach ($this->_links as $k => $v) {
                if ($v->getPath() == trim($url, '/')) {
                    unset($this->_links[$k]);
                }
            }

            return $this;
        }
    }
?>