<template>
   <div>
    <nav>
    </nav>
        <h2>Indicator Templates</h2>
        <p>These are the indicator templates that are available
        for use in the <code>indicators.json</code> file.</p>
        <ul>
            <li v-for="indicator in indicators" :key="indicator.slug_id">
                <h3>{{ indicator.title }}</h3>
                <p>{{ indicator.description }}</p>
                <p><code>{{ indicator.slug_id }}</code></p>
                <img :src="$withBase('/images/'+indicator.slug_id+'.png')" alt="Preview not included" />
            </li>
        </ul>
   </div>
  </template>
  
  <script>
    export default {
    data () {
      return {
        indicators: []
      }
    },
    mounted() {
      try {
        this.indicators = require('./indicators.json')
      } catch(error) {
        console.error(error)
      }
    }
  }
  </script>