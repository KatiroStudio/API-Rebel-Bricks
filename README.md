# 🧱 Rebel Bricks API

Une API open source qui centralise, enrichit et unifie les données Lego issues des API Brickset et Rebrickable.

## 🚀 Installation

1. Cloner le repository :
```bash
git clone https://github.com/votre-username/rebel-bricks-api.git
cd rebel-bricks-api
```

2. Installer les dépendances :
```bash
npm install
```

3. Configurer les variables d'environnement :
```bash
cp .env.example .env
```
Puis remplir les variables dans le fichier `.env` avec vos clés API.

## ⚙️ Configuration requise

- Node.js >= 18
- Compte Supabase
- Clé API Brickset
- Clé API Rebrickable

## 🏃‍♂️ Démarrage

En développement :
```bash
npm run dev
```

En production :
```bash
npm run build
npm start
```

## 📚 Documentation API

Les endpoints disponibles :

- `GET /sets?year=2024&theme=Technic` - Recherche de sets
- `GET /sets/:id` - Détails d'un set
- `GET /themes` - Liste des thèmes
- `GET /parts/:set_id` - Pièces d'un set

## 🧪 Tests

```bash
npm test
```

## 📝 Licence

MIT - Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request.

## 📧 Contact

[@Olivier](mailto:olivier@katiro.studio) 
