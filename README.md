# **Projet de fin de formation : Mobile World**

## ***Projet réalisé durant l'année 2025 pour le titre professionnel de développeur web et web mobile.***

**Site consultable en ligne via :**  
- [https://www.mobile-world.fr/](https://www.mobile-world.fr/) *(redirection)*  
- [https://mobile-world.ngo.ma6tvacoder.org/](https://mobile-world.ngo.ma6tvacoder.org/)

**Technologies utilisées :**
- Symfony 6.x  
- Bootstrap  
- PhpMyAdmin  
- Wamp (local)  
- Hébergeurs : Amen.fr, ma6tvacoder.org/

**Comment l'utiliser ? Ces commandes sont indispensables pour son bon fonctionnement :**

```bash
symfony composer install
symfony composer require knplabs/knp-paginator-bundle
```

***➕ Pour réparer EasyAdmin :***
```bash
symfony console importmap:install
symfony console assets:install
```

***🚀 En production :***
```bash
npm install
npm run build
```

***💻 Bootstrap en local :***
```bash
npm install bootstrap
npm install sass-loader sass --save-dev
```

***🧹 Videz le cache si erreur 400 ou 500 :***
```bash
php bin/console cache:clear --env=prod
```
