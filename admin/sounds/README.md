# Pasta de Sons para Notificações

## 📢 Arquivo de Som

Coloque aqui o arquivo de som para notificações:

- **Nome do arquivo**: `notification.mp3`
- **Formato**: MP3
- **Duração recomendada**: 1-3 segundos
- **Volume**: Normalizado

## 🔊 Como Adicionar

1. Baixe ou crie um som de notificação
2. Salve como `notification.mp3`
3. Coloque nesta pasta (`admin/sounds/`)
4. O sistema tocará automaticamente quando houver novos pedidos

## 💡 Sugestões de Sons

Você pode baixar sons gratuitos de:
- [Freesound.org](https://freesound.org/)
- [Zapsplat.com](https://www.zapsplat.com/)
- [Notification Sounds](https://notificationsounds.com/)

Palavras-chave para busca:
- "notification bell"
- "order alert"
- "ding"
- "chime"

## ⚙️ Configuração

Para desativar o som, edite `includes/config.php`:

```php
define('SOUND_NOTIFICATION', false);
```

## 🎵 Formato Alternativo

Se preferir usar outro formato, edite o arquivo `includes/footer.php`:

```javascript
notificationAudio = new Audio('sounds/notification.ogg'); // OGG
notificationAudio = new Audio('sounds/notification.wav'); // WAV
```

**Nota**: MP3 tem melhor compatibilidade com navegadores.
