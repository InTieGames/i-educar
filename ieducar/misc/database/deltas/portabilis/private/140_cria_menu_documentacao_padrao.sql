
-- @author   Paula Bonot <bonot@portabilis.com.br>
-- @license  @@license@@
-- @version  $Id$

INSERT INTO portal.menu_submenu VALUES (999861, 57, 2,'Documentação Padrão', 'DocumentacaoPadrao.php', null, 3);
INSERT INTO pmicontrolesis.menu VALUES (999861, 999861, 21127, 'Documentação Padrão', 0, 'DocumentacaoPadrao.php', '_self', 1, 15, 21);
INSERT INTO pmieducar.menu_tipo_usuario VALUES(1,999861,1,1,1);

-- undo

DELETE FROM pmieducar.menu_tipo_usuario WHERE ref_cod_menu_submenu = 999861;
DELETE FROM pmicontrolesis.menu WHERE cod_menu = 999861;
DELETE FROM portal.menu_submenu WHERE cod_menu_submenu = 999861;