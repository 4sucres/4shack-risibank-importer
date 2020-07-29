<p align="center"><img src="https://i.imgur.com/boxRY2S.png" width="512"></p>

<p align="center">
<img alt="Discord" src="https://img.shields.io/discord/570066757021204515?label=discord&logo=discord&style=flat-square">
</p>

# 4shack-risibank-importer

Console application construite avec [Laravel Zero](https://laravel-zero.com/) permettant d'aspirer les stickers listés sur RisiBank et hébergés sur NoelShack.

Dans l'état actuel, l'application nécessite de partager une base de données avec le projet parent [sucresware/4shack](https://github.com/sucresware/4shack), ainsi qu'un filesystem commun ou un système de stockage compatible s3.

## Commandes disponibles

```sh
# Étape 1: Importe les urls de RisiBank
php 4shack-risibank-importer import:risibank
# Étape 2: Télécharge les images depuis NoelShack
php 4shack-risibank-importer import:images
```

Les deux commandes ont été prévues pour pouvoir être reprises en cas d'interruption.

Si l'étape 1 est relancée, elle reprendra à partir du dernier sticker récupéré, jusqu'au plus récent (cronable)
