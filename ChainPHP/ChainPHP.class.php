<?php
	class ChainPHP {
		protected $arrResult;
		protected $intPassthru;
		protected $blnProgressive;
		
		public function __construct() {
			$this->arrResult = new ArrayIterator();
			$this->intPassthru = null;
			$this->blnProgressive = false;
		}
		
		public function __call($strFunction, $arrParams = array()) {
			if ($this->intPassthru !== null) {
				$arrParams = array_merge(
					array_slice($arrParams, 0, $this->intPassthru),
					array($this->arrResult->current()),
					array_slice($arrParams, $this->intPassthru)
				);
				
				if (!$this->blnProgressive) {
					$this->intPassthru = null;
				}
			}
		
			if (function_exists($strFunction)) {
				$this->_append(call_user_func_array($strFunction, $arrParams));
			} else if (method_exists($this, $strFunction)) {
				$this->_append(call_user_func_array(array($this, $strFunction), $arrParams));
			} else {
				throw new Exception('Invalid function: ' . $strFunction);
			}
			
			return $this;
		}
		
		public function _passthru($intPosition = 0, $blnProgressive = false) {
			$this->intPassthru = $intPosition;
			$this->blnProgressive = $blnProgressive;
			return $this;
		}
		
		public function _append($mxdValue) {
			$this->arrResult->append($mxdValue);
			$this->arrResult->seek($this->arrResult->count() - 1);
			return $this;
		}
		
		public function _result() {
			return $this->arrResult;
		}
		
		public function _clear() {
			$this->__construct();
			return $this;
		}
	}
?>