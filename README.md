# Yet another CSV utility plugin for CakePHP #

## License ##
MIT License

## Install ##

Top directory in your CakePHP app.

	$ git submodule add https://github.com/k1LoW/Yacsv.git app/Plugin
	

## How To Use ##

### Model ###

* app/Model/Post.php

	<?php
	
	class Sample extends AppModel {
	
		public $actsAs = array('Yacsv');
		
	}


### View ###

* app/View/Posts/import.ctp

	<?php
	
	echo $this->Form->create('Post',
						array(
							 'type' => 'file',
							 'method' => 'post',
							 'action' => 'import',
						));
	echo $this->Form->input('csv',
						array(
							'type' => 'file',
						));
	echo $this->Form->submit('Execute');
	echo $this->Form->end();


### Controller ###

* app/Controller/PostsController.php

	<?php
	
	class PostsController extends AppController {

		public function import() {
			$options = array(
						'csvEncoding' => 'SJIS-win',
						'hasHeader' => true,
						'delimiter' => "\t",
						'enclosure' => '"',
						'saveMethod' => array($this->Post, 'add'),
						'forceImport' => true,
						'allowExtension' => array('csv', 'txt'),
					   );

			try {
				$result = $this->Post->importCsv($this->requet->data, $options);
				if ($result === true) {
					$this->Session->setFlash(__('This articles has been imported'));
					if (empty($this->Post->importValidaionErrors)) {
						$this->redirect(array('action' => 'index'));
					}
				}
			} catch (Exception $e) {
				$this->Session->setFlash($e->getMessage());
			}
		}
	}


## How To Change Exception Messages ###

Generate POT files using I18N shell.

	./Console/cake i18n extract --plugin Yacsv

The default.pot file was generated at `Yacsv/Locale` directory.
Edit this file as you wish.
