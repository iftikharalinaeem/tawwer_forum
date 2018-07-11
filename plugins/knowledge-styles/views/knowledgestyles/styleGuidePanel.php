<style>
    .styleGuideNav {
        box-sizing: border-box;
        transition-property: transform;
        transition-delay: .2s;
        transition-duration: .3s;
        transition-timing-function: ease-out;
        position: fixed;
        left: 0;
        bottom: 40px;
        height: 310px;
        width: 100%;
        overflow: auto;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 20px;
        z-index: 1001;
        transform: translateY(310px);
    }

    .styleGuideNav:hover {
        transform: translateY(40px);
    }

    .styleGuideNav-link {
        color: white;
        font-size: 20px;
        line-height: 2;
    }
</style>



<nav class="styleGuideNav">
    <?php include "styleGuideNav.php"; ?>
</nav>
