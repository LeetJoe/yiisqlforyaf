<?php

/**
 * 配置
 */
class Config
{
	static $coin = array(
		'cny' => array('name' => '人民币'),
		'btc' => array('name' => '比特币'),
	);

	/**
	 * 通过文件加载配置
	 *
	 * @param $args [0] 配置路径 + 文件名
	 * @param string $args [1] 1级 KEY
	 * @param string $args [...]
	 * @param string $args [n] n级 KEY
	 *
	 * @return mixed 返回模块内容
	 */
	static function ini()
	{
		static $configs;
		$args = func_get_args();
		if (!isset($configs[$args[0]])) {
			$config = new Yaf_Config_ini(APP_PATH . '/conf/' . $args[0]);
			$configs[$args[0]] = $config->toArray();
		}
		foreach ($args as $k1 => $v1) {
			if (!$v1 && 0 !== $v1) unset($args[$k1]);
		}
		return eval('return $configs["' . join('"]["', $args) . '"];');
	}

	/**
	 * 菜单
	 *
	 * @param array $guide ['首页', '当前位置']
	 * @param string $return
	 *
	 * @return mixed|void
	 */
	static function menu($guide, $return = 'html')
	{
		$menu = self::ini('menu.ini', $guide[0]);
		if ('html' == $return) {
			isset($guide[1]) || $guide[1] = $guide[0];
			foreach ($menu as $k1 => $v1) {
				$cur = $guide[1] == $k1? ' class="cur"': '';
				echo sprintf('<a href="%s"%s>%s</a>', $v1['url'], $cur, $k1);
			}
			return null;
		}
		return $menu;
	}
}