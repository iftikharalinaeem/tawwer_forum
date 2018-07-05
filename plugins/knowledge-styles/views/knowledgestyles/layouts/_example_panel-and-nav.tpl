{extends file="panel-and-nav.tpl"}

{block name="demo"}
    <style>

        ._panelAndNav-block {
            padding: 20px;
        }

        ._panelAndNav-leftTop {
            background: orange;
            min-height: 300px;
        }

        ._panelAndNav-leftBottom {
            background: pink;
            min-height: 743px;
        }

        ._panelAndNav-top {
            background: yellow;
            min-height: 235px;
        }

        ._panelAndNav-main {
            background: green;
            min-height: 500px;
        }

        ._panelAndNav-rightTop {
            background: blue;
            min-height: 365px;
        }

        ._panelAndNav-rightBottom {
            background: red;
            min-height: 800px;
        }
    </style>
    <script>
        document.querySelector("._panelAndNav-menu").addEventListener('click', function(){
            document.querySelector("._panelAndNav-left").classList.toggle('isOpen');
        });

        document.querySelector("._panelAndNav-close").addEventListener('click', function(){
            document.querySelector("._panelAndNav-left").classList.remove('isOpen');
        });
    </script>
{/block}

{block name="topLeft"}
    <div class="panelCol">
        Top Left
    </div>
{/block}
{block name="bottomLeft"}
    <div class="panelCol">
        Bottom Left


        {*<p>Light years Vangelis quasar, bits of moving fluff inconspicuous motes of rock and gas quasar intelligent beings at the edge of forever hydrogen atoms hearts of the stars star stuff harvesting star light hundreds of thousands a mote of dust suspended in a sunbeam, are creatures of the cosmos something incredible is waiting to be known gathered by gravity. Cosmos a still more glorious dawn awaits Rig Veda finite but unbounded. As a patch of light Sea of Tranquility. Rich in heavy atoms. Jean-Francois Champollion tingling of the spine!*}

        {*<p>Radio telescope are creatures of the cosmos. Brain is the seed of intelligence kindling the energy hidden in matter realm of the galaxies, light years are creatures of the cosmos worldlets muse about venture, shores of the cosmic ocean, realm of the galaxies. Intelligent beings gathered by gravity citizens of distant epochs, shores of the cosmic ocean galaxies. Tingling of the spine are creatures of the cosmos, explorations white dwarf, the carbon in our apple pies vastness is bearable only through love billions upon billions radio telescope dispassionate extraterrestrial observer galaxies, Apollonius of Perga permanence of the stars kindling the energy hidden in matter astonishment. Another world!</p>*}

        {*<p>Intelligent beings Euclid laws of physics light years Flatland hydrogen atoms. The only home we've ever known? The carbon in our apple pies shores of the cosmic ocean rich in mystery Tunguska event realm of the galaxies. Hundreds of thousands great turbulent clouds, the only home we've ever known encyclopaedia galactica? Cosmos, great turbulent clouds, galaxies Cambrian explosion. With pretty stories for which there's little good evidence. Tingling of the spine. Cosmos, cosmic fugue. Light years. Concept of the number one Rig Veda gathered by gravity, permanence of the stars, white dwarf? Bits of moving fluff vanquish the impossible. A mote of dust suspended in a sunbeam star stuff harvesting star light. A very small stage in a vast cosmic arena, vastness is bearable only through love Tunguska event brain is the seed of intelligence corpus callosum, great turbulent clouds.</p>*}

        {*<p>Vastness is bearable only through love decipherment, quasar rich in heavy atoms hearts of the stars courage of our questions cosmos, Rig Veda. Explorations another world colonies Euclid, not a sunrise but a galaxyrise a very small stage in a vast cosmic arena? Two ghostly white figures in coveralls and helmets are soflty dancing. A mote of dust suspended in a sunbeam. Euclid colonies cosmos hearts of the stars inconspicuous motes of rock and gas culture something incredible is waiting to be known, galaxies dispassionate extraterrestrial observer Rig Veda shores of the cosmic ocean Euclid, from which we spring across the centuries Hypatia, intelligent beings, light years.</p>*}

        {*<p>Radio telescope Drake Equation. At the edge of forever extraordinary claims require extraordinary evidence quasar rich in heavy atoms? Network of wormholes explorations trillion Flatland! Across the centuries! Network of wormholes are creatures of the cosmos realm of the galaxies, encyclopaedia galactica billions upon billions? Trillion how far away cosmos take root and flourish colonies vastness is bearable only through love hundreds of thousands decipherment citizens of distant epochs tesseract. Prime number. Flatland realm of the galaxies.</p>*}

        {*<p>Hydrogen atoms. Bits of moving fluff? Hearts of the stars Cambrian explosion worldlets a very small stage in a vast cosmic arena, kindling the energy hidden in matter citizens of distant epochs at the edge of forever Flatland paroxysm of global death stirred by starlight circumnavigated! Corpus callosum trillion, science galaxies, consciousness. Permanence of the stars, encyclopaedia galactica galaxies dispassionate extraterrestrial observer at the edge of forever! How far away. Citizens of distant epochs! Drake Equation the only home we've ever known the sky calls to us light years radio telescope a mote of dust suspended in a sunbeam culture bits of moving fluff Tunguska event cosmos corpus callosum.</p>*}

        {*<p>Tunguska event decipherment billions upon billions birth kindling the energy hidden in matter. Rich in mystery gathered by gravity made in the interiors of collapsing stars finite but unbounded consciousness, descended from astronomers emerged into consciousness stirred by starlight Flatland the only home we've ever known colonies! Descended from astronomers citizens of distant epochs Hypatia great turbulent clouds across the centuries rich in mystery tendrils of gossamer clouds the carbon in our apple pies how far away culture Apollonius of Perga. Science finite but unbounded preserve and cherish that pale blue dot the only home we've ever known made in the interiors of collapsing stars something incredible is waiting to be known.</p>*}

        {*<p>Citizens of distant epochs, the only home we've ever known Cambrian explosion radio telescope, the ash of stellar alchemy consciousness rogue quasar, tesseract, Flatland. Cosmic ocean emerged into consciousness muse about. Dispassionate extraterrestrial observer finite but unbounded Orion's sword ship of the imagination cosmic ocean. Rich in heavy atoms! Flatland with pretty stories for which there's little good evidence. Hydrogen atoms how far away, Cambrian explosion network of wormholes tendrils of gossamer clouds hearts of the stars made in the interiors of collapsing stars. The carbon in our apple pies the sky calls to us.</p>*}

        {*<p>Ship of the imagination explorations a billion trillion. Realm of the galaxies explorations realm of the galaxies bits of moving fluff star stuff harvesting star light, white dwarf Vangelis permanence of the stars from which we spring shores of the cosmic ocean, prime number, radio telescope rings of Uranus gathered by gravity emerged into consciousness. Tendrils of gossamer clouds Sea of Tranquility rich in heavy atoms dispassionate extraterrestrial observer hearts of the stars, of brilliant syntheses! Consciousness culture, permanence of the stars billions upon billions explorations cosmos trillion laws of physics white dwarf of brilliant syntheses explorations Euclid. Realm of the galaxies!</p>*}

        {*<p>Dispassionate extraterrestrial observer a very small stage in a vast cosmic arena encyclopaedia galactica. The carbon in our apple pies, stirred by starlight star stuff harvesting star light. Cosmos two ghostly white figures in coveralls and helmets are soflty dancing! Paroxysm of global death cosmos rich in mystery, rich in heavy atoms. Tendrils of gossamer clouds! Descended from astronomers paroxysm of global death prime number, encyclopaedia galactica rich in mystery Orion's sword, radio telescope dream of the mind's eye and billions upon billions upon billions upon billions upon billions upon billions upon billions?</p>*}

    </div>
{/block}
{block name="top"}
    <div class="panelCol">
        Top
    </div>
{/block}
{block name="main"}
    <div class="panelCol">
        Main
    </div>
{/block}
{block name="topRight"}
    <div class="panelCol">
        Top Right
    </div>
{/block}
{block name="bottomRight"}
    <div class="panelCol">
        Bottom Right
    </div>
{/block}



