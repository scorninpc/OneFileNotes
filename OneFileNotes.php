<?php

// inicia o GTK
\Gtk::init();

// Cria a classe
class OneFileNotes
{
	private $_config;
	private $_config_file;
	public $saveTimeControl = NULL;

	public $widgets;

	/**
	 *
	 */
	public function __construct()
	{
		// default config
		$this->_config = [
			'debug' => TRUE,
			'interface' => [
				'showdecoration' => TRUE,
				'width' => 200,
				'height' => 200,
				'x' => 0,
				'y' => 0
			],
			'sourceview' => [
				'showlinenumbers' => TRUE,
			],
		];

		// salva a configuração na primeira vez
		$this->_config_file = ONEFILENOTES_CONFIG_PATH . "/config.json";

		if(!file_exists($this->_config_file)) {
			$this->_debug("Salvando primeiras configurações em " . $this->_config_file);

			if(!file_exists(ONEFILENOTES_CONFIG_PATH)) {
				mkdir(ONEFILENOTES_CONFIG_PATH);
			}

			file_put_contents($this->_config_file, json_encode($this->_config));
		}
		else {
			$this->_debug("Lendo configurações de " . $this->_config_file);
			$this->_config = json_decode(file_get_contents($this->_config_file), TRUE);
		}
		
		// cria a interface
		$this->createInterface();
		
		// mostra tudo
		$this->widgets['wndMain']->show_all();

		// seta o novo tamanho
		$this->widgets['wndMain']->set_size_request($this->_config['interface']['width'], $this->_config['interface']['height']);
		$this->widgets['wndMain']->move($this->_config['interface']['x'], $this->_config['interface']['y']);

		// inicia o loop
		\Gtk::main();
	}

	/**
	 * cria a interface
	 */
	public function createInterface()
	{
		// cria o form
		$this->widgets['wndMain'] = new \GtkWindow(\Gtk::WINDOW_TOPLEVEL);
		$this->widgets['wndMain']->set_title("OnFileNotes");
		$this->widgets['wndMain']->set_keep_above(TRUE);
		$this->widgets['wndMain']->set_decorated($this->_config['interface']['showdecoration']);
		$this->widgets['wndMain']->set_resizable(TRUE);

		// cria o box principal
		$vbox = new \GtkBox(\GtkOrientation::VERTICAL);
		$vbox->set_border_width(0);
		$this->widgets['wndMain']->add($vbox);
		
		// cria a barra de menu
		$menubar = new \GtkMenuBar();
		$vbox->pack_start($menubar, FALSE, FALSE, 0);

			// create submenu File
			$menu = new \GtkMenu();
			$menu->append($this->widgets['mnuNewFile']=\GtkMenuItem::new_with_label("New file"));
			$menu->append($this->widgets['mnuShowDecoration']=\GtkCheckMenuItem::new_with_label("Window Decoration"));
			$menu->append($this->widgets['mnuShowLineNumbers']=\GtkCheckMenuItem::new_with_label("Show Line Numbers"));
			$menu->append(new \GtkSeparatorMenuItem());
			$menu->append($this->widgets['mnuExit']=\GtkMenuItem::new_with_label("Exit"));

			$menuitem = \GtkMenuItem::new_with_label("File");
			$menuitem->set_submenu($menu);
			$menubar->append($menuitem);
		
			$this->widgets['mnuExit']->connect("activate", function($widget) {
				\Gtk::main_quit();
			});
		
			if($this->_config['interface']['showdecoration']) {
				$this->widgets['mnuShowDecoration']->set_active(TRUE);
			}
			$this->widgets['mnuShowDecoration']->connect("activate", function($widget) {
				$this->_config['interface']['showdecoration'] = FALSE;
				if($widget->get_active()) {
					$this->_config['interface']['showdecoration'] = TRUE;
					
				}
				$this->widgets['wndMain']->set_decorated($this->_config['interface']['showdecoration']);

				// salva a configuração
				$this->saveConfig();
			});
		
			if($this->_config['sourceview']['showlinenumbers']) {
				$this->widgets['mnuShowLineNumbers']->set_active(TRUE);
			}
			$this->widgets['mnuShowLineNumbers']->connect("activate", function($widget) {
				// percorre os sourcesview
				foreach($this->widgets['sourceView'] as $sourceView) {

					$this->_config['sourceview']['showlinenumbers'] = FALSE;
					if($widget->get_active()) {
						$this->_config['sourceview']['showlinenumbers'] = TRUE;	
					}

					$sourceView->set_show_line_marks($this->_config['sourceview']['showlinenumbers']);
					
				}

				// salva a configuração
				$this->saveConfig();
			});

		// cria as abas
		$this->widgets['notebook'] = new \GtkNotebook();
		$vbox->pack_start($this->widgets['notebook'], TRUE, TRUE, 0);

		$this->createNewTab("Geral");
		$this->createNewTab("SiNCORE");
		
		// cria os sinais
		$this->widgets['wndMain']->connect("destroy", function($widget) {
			$this->saveConfig();
			\Gtk::main_quit();
		});

		// ao mover
		$this->widgets['wndMain']->connect("configure-event", function($widget, $event) {
			$this->_config['interface']['x'] = $event->configure->x;
			$this->_config['interface']['y'] = $event->configure->y;
			$this->_config['interface']['width'] = $event->configure->width;
			$this->_config['interface']['height'] = $event->configure->height;

			// chama a requisição de um salvamento
			$this->saveConfig();
		});
	}

	/**
	 * cria uma nova aba
	 */
	public function createNewTab($name)
	{
		// eventbox para criação do label e suportar menu de contexto
		$eventbox = new \GtkEventBox();
		$eventbox->add(new \GtkLabel($name));
		$eventbox->show_all();

		// cria as configurações do sourceview
		$this->widgets['sourceLanguageManager'] = new \GtkSourceLanguageManager();
		$lang = $this->widgets['sourceLanguageManager']->get_language("markdown");

		// cria o buffer
		$sourceBuffer = new \GtkSourceBuffer();
		$this->widgets['sourceBuffer'][] = $sourceBuffer;
		$sourceBuffer->set_language($lang);

		// cria o sourceview
		$sourceView = \GtkSourceView::new_with_buffer($sourceBuffer);
		$sourceView->set_show_line_numbers(false);
		$sourceView->set_auto_indent(true);
		$sourceView->set_indent_on_tab(true);
		
		if($this->_config['sourceview']['showlinenumbers']) {
			$sourceView->set_show_line_marks(TRUE);
		}
		else {
			$sourceView->set_show_line_marks(FALSE);
		}

		$sourceView->set_tab_width(4);
		$sourceView->set_tab_width(4);
		$this->widgets['sourceView'][] = $sourceView;

		// carrega o ultimo conteudo
		$sourceBuffer->set_text("# OK jovem\n - lista 1\n - lista 2");

		// adiciona o source view ao scrool e no window
		$scroll = new \GtkScrolledWindow();
		$scroll->add($sourceView);

		// adiciona a pagina
		$pagina = $this->widgets['notebook']->append_page($scroll, $eventbox);
		$this->_debug("Pagina " . $pagina . " adicionada");

		// adiciona o evento ao eventbox
		$eventbox->connect("button-release-event", function($widget, $event) use ($scroll) {
	
			// right click
			if($event->button->button == 3) {

				// recupera a pagina
				$pagina = $this->widgets['notebook']->page_num($scroll);
	
				// cria o menu
				$menu = new \GtkMenu();
				$menu->append($mnuClose=\GtkMenuItem::new_with_label("Fechar"));
				$menu->show_all();

				// menu fechar
				$mnuClose->connect("activate", function() use ($pagina) {
					$this->widgets['notebook']->remove_page($pagina);
				});
				
				// mostra o menu
				$menu->popup_at_pointer($event);
			}
	
		});

		
	}

	/**
	 * função que chama o salvamento das configurações
	 */
	public function saveConfig()
	{
		// se ja tiver nada esperando, cancela
		if($this->saveTimeControl != NULL) {
			\Gtk::source_remove($this->saveTimeControl);
		}

		// cria uma nova
		$this->saveTimeControl = \Gtk::timeout_add(1000, function() {

			// salva o arquivo
			file_put_contents($this->_config_file, json_encode($this->_config));

			//
			$this->_debug("SALVOU!");

			// finaliza o temporizador
			$this->saveTimeControl = NULL;
			return FALSE;
		});
	}

	/**
	 * debuga mensagens no console
	 */
	private function _debug($msg)
	{
		if($this->_config['debug']) {
			echo "DEBUG: " . $msg . "\n";
		}
	}


}

// configura alguns paths
defined("ONEFILENOTES_PATH") || define("ONEFILENOTES_PATH", dirname(__FILE__));
defined("ONEFILENOTES_CONFIG_PATH") || define("ONEFILENOTES_CONFIG_PATH", getenv("HOME") . "/.config/OneFileNotes");

// inicia
new \OneFileNotes();