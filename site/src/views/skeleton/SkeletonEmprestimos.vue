<script>

export default {
	name: 'City',
	props: {
		loading: Boolean
	},
	setup() {
		return {
			
		};
	},
	
};
</script>

<template>
	<div v-if="loading" class=" bg-white border-round border-1 surface-border p-4">
		<div class="grid flex flex-wrap mb-3 px-4 pt-2">
			<Skeleton height="1rem" width="6rem" class="col-8 px-0 py-0 mb-2"></Skeleton>
			<Skeleton height="2rem" class="col-8 px-0 py-0 mb-2"></Skeleton>
			<Skeleton height="1rem" width="6rem" class="col-8 px-0 py-0 mb-2"></Skeleton>
			<Skeleton height="2rem" class="col-8 px-0 py-0 mb-2"></Skeleton>
			<Skeleton height="1rem" width="6rem" class="col-8 px-0 py-0 mb-2"></Skeleton>
			<Skeleton height="2rem" class="col-8 px-0 py-0 mb-2"></Skeleton>
		</div>
		<Skeleton width="100%" height="150px"></Skeleton>
		<div class="flex justify-content-between mt-3">
			<Skeleton width="4rem" height="2rem"></Skeleton>
			<Skeleton width="4rem" height="2rem"></Skeleton>
		</div>
	</div>
</template>
