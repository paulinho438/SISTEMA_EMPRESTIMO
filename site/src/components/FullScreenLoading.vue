<template>
    <div v-if="isLoading" class="fullscreen-loading">
      <div class="spinner"></div>
    </div>
  </template>
  
  <script>
  export default {
    name: "FullScreenLoading",
    props: {
      isLoading: {
        type: Boolean,
        required: true,
      },
    },
  };
  </script>
  
  <style scoped>
  .fullscreen-loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8); /* Fundo branco semi-transparente */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999; /* Certifique-se de que está acima de outros elementos */
  }
  
  .spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #ddd;
    border-top: 5px solid #007bff; /* Cor principal do carregamento */
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }
  
  @keyframes spin {
    from {
      transform: rotate(0deg);
    }
    to {
      transform: rotate(360deg);
    }
  }
  </style>
  