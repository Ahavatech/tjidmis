<?php

import('plugins.themes.default.DefaultThemePlugin');

class JidmisThemePlugin extends DefaultThemePlugin
{
	public function init()
	{
		parent::init();
		$this->addStyle('jidmisTheme', 'css/theme.css');
		$this->addStyle('jidmisFa', 'css/fontawesome.css');
		$this->addStyle('jidmisFonts', 'css/google-fonts.css');
		$this->addStyle('jidmisJournal', 'css/journal.css');
		HookRegistry::register('Templates::Common::Sidebar', array($this, 'renderSidebar'));
	}

	public function renderSidebar($hookName, $args)
	{
		$file = $this->getPluginPath() . '/sidebar.html';
		if (is_readable($file)) {
			$args[2] .= file_get_contents($file);
		}
		return false;
	}

	public function getDisplayName()
	{
		return 'JIDMIS';
	}

	public function getDescription()
	{
		return 'JIDMIS public-site theme for OJS 3.3.';
	}
}
