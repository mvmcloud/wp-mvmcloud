=== WP-MVMCloud Integraçã0 ===

Desenvolvedor: MVMCloud
Reque ao menos: 5.0
Testado até: 6.2
Tag estável: 1.0.28
Tags: mvmcloud, tracking, statistics, stats, analytics

Adiciona estatísticas do MVMCloud Analytics ao seu painel do WordPress e também é capaz de adicionar o Código de Rastreamento do MVMCloud Analytics ao seu blog.

== Descrição ==

Se você estiver usando o MVMCloud Analytics e quiser adicionar as estatísticas do seu site ao painel do WordPress, use o [plugin MVMCloud para WordPress](https://github.com/mvmcloud/wp-mvmcloud).

Este plugin usa a API MVMCloud Analytics para mostrar suas estatísticas do MVMCloud Analytics em seu painel do WordPress. Também é possível adicionar o código de rastreamento do MVMCloud Analytics ao seu blog e fazer algumas modificações no código de rastreamento. Além disso, WP-MVMCloud oferece suporte a redes WordPress e gerencia vários sites e seus códigos de rastreamento.

Para utilizar este plugin é necessário o aplicativo de web analytics MVMCloud Analytics. Se você não possui uma assinatura, você pode obter uma em [MVMCloud Analytics](https://www.mvmcloud.net/en/analytics).

**Requisitos:** PHP 7.0 (ou superior), WordPress 5.0 (ou superior), MVMCloud Analytics 4.0 (ou superior)

== Idiomas Suportados==

Este plugin é compatível com inglês e português brasileiro. Você pode obter a versão em inglês deste arquivo em (You can get the english version of this file at) [WP-MVMCloud english](https://github.com/mvmcloud/wp-mvmcloud/blob/master/readme.txt)

= O que é MVMCloud Analytics? =

MVMCloud Analytics é uma plataforma de software de análise web. Ele fornece relatórios detalhados sobre o seu site e seus visitantes, incluindo os mecanismos de pesquisa e as palavras-chave que eles usaram, o idioma que falam, quais páginas gostam,
os arquivos que eles baixam e muito mais.

= Primeiros passos =
- Você precisa de uma assinatura do MVMCloud Analytics. Caso não tenha, você pode obtê-lo em [MVMCloud Analytics](https://www.mvmcloud.net/en/analytics);
- Faça o download do plugin em https://github.com/mvmcloud/wp-mvmcloud/releases/download/stable/wp-mvmcloud.2.0.28.zip;
- Instale e ative este plugin na sua instalação do WordPress;
- Configure o plugin para acessar a sua instância do MVMCloud Analytics;
- Instale, através deste plugin, o código de rastreamento do MVMCloud Analytics na tag head do seu WordPress;
- Navegue até o painel do WordPress e você verá um novo item de menu chamado WP-MVMCLOUD;
- Clique nele para ver as métricas do seu site.

= Shortcodes =
Você pode usar os seguintes códigos de acesso, se ativados:

    [wp-mvmcloud module="overview" title="" period="day" date="yesterday"]
Mostra uma tabela de visão geral como o painel de visão geral do WP-MVMCloud. Múltiplas matrizes de dados serão acumuladas. Se você preencher o atributo title, seu conteúdo será mostrado no título da tabela.

    [wp-mvmcloud module="opt-out" language="en" width="100%" height="200px"]
Mostra o Iframe de desativação do MVMCloud Analytics. Você pode alterar o idioma do Iframe pelo atributo idioma (ex: pt-br para português brasileiro) e sua largura e altura usando os atributos correspondentes.

    [wp-mvmcloud module="post" range="last30" key="sum_daily_nb_uniq_visitors"]
Mostra o valor das chaves escolhidas relacionado ao post atual. Você pode definir um intervalo (formato: lastN, previousN ou YYYY-MM-DD,YYYY-MM-DD) e a chave do valor desejado (por exemplo, sum_daily_nb_uniq_visitors, nb_visits ou nb_hits - para detalhes consulte o método API Actions.getPageUrl do MVMCloud Analytics usando um faixa).

    [wp-mvmcloud]
É igual a *[wp-mvmcloud module="overview" title="" period="day" date="yesterday"]*.

Obrigado!

== Perguntas Fequentes ==

= Onde posso encontrar a URL do MVMCloud Analytics e o token de autenticação do MVMCloud Analytics? =

Para usar este plugin você precisará de sua própria assinatura do MVMCloud Analytics. Se você não tiver um, poderá obtê-lo em [MVMCloud Analytics](https://www.mvmcloud.net/en/analytics).

Assim que o MVMCloud Analytics funcionar, você poderá configurar o WP-MVMCloud: A URL do MVMCloud Analytics é a mesma URL que você usa para acessar seu MVMCloud Analytics, por exemplo. para o site de demonstração: https://analytics.mvmcloud.net. O token de autenticação é uma espécie de senha secreta, que permite ao WP-MVMCloud obter os dados necessários do MVMCloud Analytics. Para obter seu token de autenticação, faça login no MVMCloud Analytics, clique no ícone de engrenagem de Administração (canto superior direito) e clique em “Pessoal” e “Segurança” (menu da barra lateral esquerda).

Você pode criar quantos tokens quiser, recomendamos que você crie um apenas para usar neste plugin.

= Recebo esta mensagem: "WP-MVMCloud (WP-MVMCloud) não conseguiu se conectar ao MVMCloud Analytics (Mvmcloud) usando nossa configuração". Como proceder? =

Primeiro, certifique-se de que sua configuração seja válida, por exemplo, se você estiver usando a URL correta do MVMCloud Analytics (veja a descrição acima). Em seguida, vá até a aba “Suporte” e execute o script de teste. Este script de teste tentará obter algumas informações do MVMCloud Analytics e mostrará a resposta completa. Normalmente, a saída da resposta dá uma dica clara do que há de errado:

A saída da resposta contém...

* **bool(false)** e **HTTP/1.1 403 Proibido**: WP-MVMCloud não tem permissão para se conectar ao MVMCloud Analytics. Verifique a configuração do seu servidor MVMCloud Analytics. Talvez você esteja usando uma proteção por senha via .htaccess ou bloqueando solicitações de localhost/127.0.0.1. Se você não tiver certeza sobre isso, entre em contato com seu host da web para obter suporte.
* **bool(false)** e **HTTP/1.1 404 Not Found**: A URL do MVMCloud Analytics está errada. Tente copiar e colar a URL que você usa para acessar o próprio MVMCloud Analytics via navegador.
* **bool(false)** e nenhum código de resposta HTTP adicional: O servidor MVMCloud Analytics não responde. Muitas vezes, isso é causado por configurações de firewall ou mod_security. Verifique os arquivos de log do seu servidor para obter mais informações. Se você não tiver certeza sobre isso, entre em contato com seu host da web para obter suporte.

= Verificador de compatibilidade de PHP relata problemas de compatibilidade de PHP7 com WP-MVMCloud. =

O Verificador de Compatibilidade mostra dois falsos positivos. WP-MVMCloud é 100% compatível com PHP7, você pode ignorar o relatório.

= WP-MVMCloud mostra apenas os primeiros 100 sites da minha rede multisite. Como posso obter todos os outros sites? =

A API do MVMCloud Analytics é limitada a 100 sites por padrão. Você pode abrir um ticket de suporte para solicitar mais sites.

= O rastreamento não funciona no HostGator! =

Tente habilitar a opção "evitar mod_security" (configurações do WP-MVMCloud, aba Tracking) ou crie uma lista de permissões do mod_security.

Muito obrigado! :-)

== Instalação ==

= Notas Gerais =
* Primeiro, você deve configurar uma instância do MVMCloud Analytics em execução. Se você não tiver um, poderá obtê-lo em [MVMCloud Analytics](https://www.mvmcloud.net/en/analytics).

= Instale o WP-MVMCloud em um blog WordPress simples =

1. Carregue o diretório `wp-mvmcloud` completo em seu diretório `wp-content/plugins`.
2. Ative o plugin através do menu ‘Plugins’ do WordPress.
3. Abra o novo menu ‘Configurações/Configurações do WP-MVMCloud (WP-MVMCloud)’ e siga as instruções para configurar sua conexão do MVMCloud Analytics. Salvar configurações.
4. Se você tiver acesso a várias estatísticas do site e não ativou a "configuração automática", escolha seu blog e salve as configurações novamente.
5. Consulte 'Dashboard/WP-MVMCloud' para ver as estatísticas do seu site.

= Instale o WP-MVMCloud em uma rede de blog WordPress (WPMU/WP multisite) =

Existem dois métodos diferentes para usar o WP-MVMCloud em um ambiente multisite:

* Como um plugin específico do site, ele se comporta como um plugin instalado em um simples blog WordPress. Cada usuário pode habilitar, configurar e usar o WP-MVMCloud por conta própria. Os usuários podem até usar suas próprias instâncias do MVMCloud Analytics (e, portanto, precisam).
* Usar WP-MVMCloud como plugin de rede equivale a uma abordagem central. Uma única instância do MVMCloud Analytics é usada e o administrador do site configura o plugin completamente. Os usuários só podem ver suas próprias estatísticas, os administradores do site podem ver as estatísticas de cada blog.

*Plugin específico do site*

Basta adicionar WP-MVMCloud à sua pasta /wp-content/plugins e ativar a página Plugins para administradores de sites individuais. Cada usuário deve habilitar e configurar o WP-MVMCloud por conta própria se quiser usar o plugin.

*Plug-in de rede*

O suporte ao Plug-in de rede ainda é experimental. Teste-o você mesmo (por exemplo, usando uma cópia local do seu multisite WP) antes de usá-lo em um contexto de usuário.

Adicione WP-MVMCloud à sua pasta /wp-content/plugins e habilite-o como [Network Plugin](http://codex.wordpress.org/Create_A_Network#WordPress_Plugins). Os usuários podem acessar suas próprias estatísticas, os administradores do site podem acessar as estatísticas de cada blog e a configuração do plugin.

== Capturas de tela ==

1. Configurações do WP-MVMCloud.
2. Página de estatísticas do WP-MVMCloud.
3. Observe mais de perto um gráfico de pizza.
