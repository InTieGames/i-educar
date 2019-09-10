<?php

require_once ("include/clsBase.inc.php");
require_once ("include/clsCadastro.inc.php");
require_once ("include/clsBanco.inc.php");
require_once( "include/public/geral.inc.php" );
require_once ("include/pmieducar/geral.inc.php");
require_once ("include/modules/clsModulesAuditoriaGeral.inc.php");
require_once 'App/Model/Pais.php';
require_once 'App/Model/NivelAcesso.php';

class clsIndexBase extends clsBase
{
    function Formular()
    {
        $this->SetTitulo( "{$this->_instituicao} Uf" );
        $this->processoAp = "754";
        $this->addEstilo('localizacaoSistema');
    }
}

class indice extends clsCadastro
{
    /**
     * Referencia pega da session para o idpes do usuario atual
     *
     * @var int
     */
    var $pessoa_logada;

    var $sigla_uf;
    var $nome;
    var $geom;
    var $idpais;
    var $cod_ibge;

    function Inicializar()
    {
        $retorno = "Novo";


        $this->sigla_uf=$_GET["sigla_uf"];

        if( is_string( $this->sigla_uf ) )
        {
            $obj = new clsPublicUf( $this->sigla_uf );
            $registro  = $obj->detalhe();
            if( $registro )
            {
                foreach( $registro AS $campo => $val )  // passa todos os valores obtidos no registro para atributos do objeto
                    $this->$campo = $val;


//              $this->fexcluir = true;

                $retorno = "Editar";
            }
        }
        $this->url_cancelar = ($retorno == "Editar") ? "public_uf_det.php?sigla_uf={$registro["sigla_uf"]}" : "public_uf_lst.php";
        $this->nome_url_cancelar = "Cancelar";

    $nomeMenu = $retorno == "Editar" ? $retorno : "Cadastrar";
    $localizacao = new LocalizacaoSistema();
    $localizacao->entradaCaminhos( array(
         $_SERVER['SERVER_NAME']."/intranet" => "In&iacute;cio",
         "educar_enderecamento_index.php"    => "Endereçamento",
         ""        => "{$nomeMenu} UF"
    ));
    $this->enviaLocalizacao($localizacao->montar());
        return $retorno;
    }

    function Gerar()
    {
        // foreign keys
        $opcoes = array( "" => "Selecione" );
        if( class_exists( "clsPais" ) )
        {
            $objTemp = new clsPais();
            $lista = $objTemp->lista( false, false, false, false, false, "nome ASC" );
            if ( is_array( $lista ) && count( $lista ) )
            {
                foreach ( $lista as $registro )
                {
                    $opcoes["{$registro['idpais']}"] = "{$registro['nome']}";
                }
            }
        }
        else
        {
            echo "<!--\nErro\nClasse clsPais nao encontrada\n-->";
            $opcoes = array( "" => "Erro na geracao" );
        }
        $this->campoLista( "idpais", "Pais", $opcoes, $this->idpais );


        // text
        $this->campoTexto( "sigla_uf", "Sigla Uf", $this->sigla_uf, 3, 3, true );
        $this->campoTexto( "nome", "Nome", $this->nome, 30, 30, true );
        $this->campoNumero( "cod_ibge", "C&oacute;digo INEP", $this->cod_ibge);
//      $this->campoTexto( "geom", "Geom", $this->geom, 30, 255, false );

                $scripts = array(
            '/modules/Portabilis/Assets/Javascripts/cad_uf.js'
            );

        Portabilis_View_Helper_Application::loadJavascript($this, $scripts);
    }

    function Novo()
    {


        if ($this->idpais == App_Model_Pais::BRASIL && $this->nivelAcessoPessoaLogada() != App_Model_NivelAcesso::POLI_INSTITUCIONAL) {
            $this->mensagem = 'Não é permitido cadastro de UFs brasileiras, pois já estão previamente cadastrados.<br>';
            return false;
        }

        $obj = new clsPublicUf(strtoupper($this->sigla_uf));
        $duplica= $obj->verificaDuplicidade();
        if($duplica){
            $this->mensagem = "A sigla já existe para outro estado.<br>";
            return false;
        }else{



        $obj = new clsPublicUf( $this->sigla_uf, $this->nome, $this->geom, $this->idpais, $this->cod_ibge );
        $cadastrou = $obj->cadastra();
        if( $cadastrou )
        {
            $enderecamento = new clsPublicUf($cadastrou);
            $enderecamento->cadastrou = $cadastrou;
            $enderecamento = $enderecamento->detalhe();
            $auditoria = new clsModulesAuditoriaGeral("Endereçamento de Estado", $this->pessoa_logada, $cadastrou);
            $auditoria->inclusao($enderecamento);

            $this->mensagem .= "Cadastro efetuado com sucesso.<br>";
            $this->simpleRedirect('public_uf_lst.php');
        }

        $this->mensagem = "Cadastro n&atilde;o realizado.<br>";

        return false;
    }
    }
    function Editar()
    {


        if ($this->idpais == App_Model_Pais::BRASIL && $this->nivelAcessoPessoaLogada() != App_Model_NivelAcesso::POLI_INSTITUCIONAL) {
            $this->mensagem = 'Não é permitido edição de UFs brasileiras, pois já estão previamente cadastrados.<br>';
            return false;
        }

        $enderecamentoDetalhe = new clsPublicUf($this->sigla_uf);
        $enderecamentoDetalhe->cadastrou = $this->sigla_uf;
        $enderecamentoDetalheAntes = $enderecamentoDetalhe->detalhe();

        $obj = new clsPublicUf(strtoupper($this->sigla_uf));
        $duplica= $obj->verificaDuplicidade();
        if($duplica){
            $this->mensagem = "A sigla já existe para outro estado.<br>";
            return false;
        }else{
        $obj = new clsPublicUf( $this->sigla_uf, $this->nome, $this->geom, $this->idpais, $this->cod_ibge );
        $editou = $obj->edita();
        if( $editou )
        {
            $enderecamentoDetalheDepois = $enderecamentoDetalhe->detalhe();
            $auditoria = new clsModulesAuditoriaGeral("Endereçamento de Estado", $this->pessoa_logada, $this->sigla_uf);
            $auditoria->alteracao($enderecamentoDetalheAntes, $enderecamentoDetalheDepois);

            $this->mensagem .= "Edi&ccedil;&atilde;o efetuada com sucesso.<br>";
            $this->simpleRedirect('public_uf_lst.php');
        }

        $this->mensagem = "Edi&ccedil;&atilde;o n&atilde;o realizada.<br>";

        return false;
    }
}
    function Excluir()
    {


        if ($this->idpais == App_Model_Pais::BRASIL && $this->nivelAcessoPessoaLogada() != App_Model_NivelAcesso::POLI_INSTITUCIONAL) {
            $this->mensagem = 'Não é permitido exclusão de UFs brasileiras, pois já estão previamente cadastrados.<br>';
            return false;
        }
        $enderecamento = $obj->detalhe();
        $enderecamentoDetalhe =  $this->sigla_uf;

        $obj = new clsPublicUf( $this->sigla_uf );
        $excluiu = $obj->excluir();
        if( $excluiu )
        {
            $auditoria = new clsPublicUf("Endereçamento de Estado", $this->pessoa_logada, $this->sigla_uf);
            $auditoria->exclusao($enderecamento);

            $this->mensagem .= "Exclus&atilde;o efetuada com sucesso.<br>";
            $this->simpleRedirect('public_uf_lst.php');
        }

        $this->mensagem = "Exclus&atilde;o n&atilde;o realizada.<br>";

        return false;
    }
}

// cria uma extensao da classe base
$pagina = new clsIndexBase();
// cria o conteudo
$miolo = new indice();
// adiciona o conteudo na clsBase
$pagina->addForm( $miolo );
// gera o html
$pagina->MakeAll();
?>
