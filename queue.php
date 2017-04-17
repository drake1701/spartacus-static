<?php
/**
 * @author 	dennis
 * @site 	www.drogers.net
 */
 
class Queue {

	protected $types = array(
		'1' => 'm-th-sa',
		'2' => 'monthly-1'
	);

    public function getNext($date, $type){
        if(is_string($date)){
            $date = new DateTime($date);
        }
        switch($this->types[$type]){
            case "m-th-sa":
                do {
                    $last_dow = $date->format("w");
                    if ($last_dow == 1) {
                        $date->add(new DateInterval("P3D"));
                    } else {
                        $date->add(new DateInterval("P2D"));
                    }
                } while($date->format("d") == 1);
                break;
            case "monthly-1":
                $date->add(new DateInterval("P1M"));
                break;
            default:
                break;
        }
        return $date->format("Y-m-d 00:00:00");
    }

	public function getLastQueuedDate($type){
		$last = new DateTime($this->getLastDate($type));
        return $this->getNext($last, $type);
	}

	public function getLastDate($type) {
	    global $db;
        $result = $db->query("SELECT * FROM entry WHERE queue = $type ORDER BY published_at DESC LIMIT 1;")->fetchArray();
		return $result['published_at'];
	}

    public function getLastPublishedDate($type) {
	    global $db;
        $result = $db->query("SELECT * FROM entry WHERE published IS NOT NULL AND queue = $type ORDER BY published_at DESC LIMIT 1;")->fetchArray();
		return $result['published_at'];
    }

	public function getNextQueueEntry() {
		$entry = new PaperRoll_Model_Entry();
		$db = $entry->getResource();
		$result = $db->fetchRow($db->select()
			->where("published IS NULL")
			->where("published_at < NOW()")
			->order('published_at ASC')
			->limit(1));
		if(count($result)){
			return $entry->load($result->id);
		}
		return false;
	}

	public function popQueue() {
		while($entry = $this->getNextQueueEntry()){
			$entry->setData('published', 1);
			$entry->save();
			Paper::log($entry->getData('title'));
		}
	}

    public function reorder(Array $ids, $type) {
        $last = $this->getLastPublishedDate();
        $next = $this->getNext($last, $type);
        foreach($ids as $id){
            $entry = new PaperRoll_Model_Entry();
            $entry->load($id);
            if($entry->getData('published_at') != $next){
                $entry->setData('published_at', $next)->save();
            }
            $next = $this->getNext($next, $type);
        }
    }

}