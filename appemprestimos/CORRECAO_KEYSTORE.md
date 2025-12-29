# ✅ Correção: Caminho do Keystore

## ❌ Problema

O erro ocorreu porque o caminho do keystore no arquivo `keystore.properties` estava incorreto:
```
storeFile=app/appemprestimos-release.keystore  ❌ (ERRADO)
```

Como o `build.gradle` está em `android/app/`, o caminho deve ser relativo a essa pasta.

## ✅ Solução Aplicada

Corrigi o caminho para:
```
storeFile=appemprestimos-release.keystore  ✅ (CORRETO)
```

O keystore está localizado em:
- `android/app/appemprestimos-release.keystore`

E o `build.gradle` está em:
- `android/app/build.gradle`

Portanto, o caminho relativo correto é apenas o nome do arquivo.

---

## 🚀 Próximo Passo

Agora você pode tentar gerar o bundle novamente:

```bash
cd android
gradlew.bat bundleRelease
```

O build deve encontrar o keystore corretamente agora!

