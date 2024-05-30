<script>
export default {
	name: 'Loading',
	props: {
		loading: Boolean
	}
};
</script>

<template>
	<div v-if="loading">
		<div class="absolute z-4 top-0 left-0 px-4 w-full h-full bg-gray-100 opacity-60"></div>
		<div class="grid absolute z-5 top-0 left-0 px-4 w-full h-full justify-content-center0">
			<div class="col-12 justify-content-center align-items-center flex">
				<ProgressSpinner strokeWidth="4" class="w-2" />
			</div>
		</div>
	</div>
</template>
