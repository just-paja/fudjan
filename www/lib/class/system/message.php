<?

namespace System
{
	class Message
	{
		private $id, $status, $title, $message;
		private $links = array();
		private $autohide = false;


		public function __construct()
		{
			list( $this->status, $this->title, $this->message, $this->autohide, $this->links, $no_queue ) = func_get_args();

			if(is_array($this->message)){
				$this->message = ucfirst(strtolower(implode(', ', $this->message))).'.';
			}
			$this->id = spl_object_hash($this);

			if (!$no_queue) {
				self::enqueue($this);
			}
		}

		public static function &get_all() { return $_SESSION['messages']; }
		public function __destruct()  { self::dequeue($this);     }
		public function get_id()      { return $this->id;         }
		public function get_status()  { return $this->status;     }
		public function get_title()   { return $this->title;      }
		public function get_message() { return $this->message;    }
		public function get_links()   { return $this->links;      }
		public function autohides()   { return !!$this->autohide; }


		public function get_retval()
		{
			if($this->status == 'success') return true;
			if($this->status == 'error') return false;
			if($this->status == 'info') return null;
		}


		public function dequeue($obj = null)
		{
			if($obj instanceof self) unset($_SESSION['messages'][$this->id]);
			elseif($obj) unset($_SESSION['messages'][$this->id]);
		}


		public static function dequeue_all()
		{
			foreach ((array) $_SESSION['messages'] as $msg) {
				$msg->__destruct();
			}
		}


		public static function enqueue(self $msg)
		{
			$_SESSION['messages'][$msg->get_id()] = $msg;
		}
	}
}
