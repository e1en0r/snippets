<?php
	class ExtendedChainPHP extends ChainPHP {
		public function _array() {
			$this->_append(func_get_args());
			return $this;
		}
	
		public function _echo($mxdValue = null) {
			print $this->intPassthru !== null ? $this->arrResult->current() : $mxdValue;
			return $this;
		}
	}
?>