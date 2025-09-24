<template>
    <div v-if="colors && filteredColors && colors.length">
        <div class="row no-gutters mb-3">
            <div class="col-sm-2">
                <div class="text-secondary fs-14 fw-400 mt-2">Color</div>
            </div>
            <div class="col-sm-10">
                <div class="aiz-radio-inline">
                    <label
                        v-for="(color, index) in colors"
                        :key="index"
                        class="aiz-megabox pl-0 mr-2 mb-0"
                        :data-toggle="'tooltip'"
                        :data-title="getSingleColorName(color)"
                    >
                        <input
                            type="radio"
                            name="color"
                            :value="getSingleColorName(color)"
                            :checked="index === 0"
                        />
                        <span
                            class="aiz-megabox-elem rounded-0 d-flex align-items-center justify-content-center p-1"
                        >
              <span
                  class="size-25px d-inline-block rounded"
                  :style="{
                  backgroundImage: `url(${getSingleColorImage(color)})`,
                  backgroundSize: 'cover',
                  backgroundPosition: 'center',
                  display: 'inline-block'
                }"
              ></span>
            </span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        colors: {
            type: Array,
            required: true
        },
        filteredColors: {
            type: Array,
            required: true
        }
    },
    methods: {
        getSingleColorName(color) {
            // serverdə blade'dən gəlmirsə, bunu JS-də yazmalısan
            return color.name || color;
        },
        getSingleColorImage(color) {
            // color image URL-nu qaytar — bu da serverdən gəlmirsə, bura yazmalısan
            return `/images/colors/${color}.png`; // nümunə olaraq
        }
    }
};
</script>
