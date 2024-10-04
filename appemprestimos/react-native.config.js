module.exports = {
  dependencies: {
    'react-native-vector-icons': {
      platforms: {
        ios: null,
      },
    },
  },
  project: {
    android: {
      unstable_reactLegacyComponentNames: [
        'AIRMap',
        'AIRMapCallout',
        'AIRMapCalloutSubview',
        'AIRMapCircle',
        'AIRMapHeatmap',
        'AIRMapLocalTile',
        'AIRMapMarker',
        'AIRMapOverlay',
        'AIRMapPolygon',
        'AIRMapPolyline',
        'AIRMapUrlTile',
        'AIRMapWMSTile',
      ],
    },
    ios: {
      unstable_reactLegacyComponentNames: [
        'AIRMap',
        'AIRMapCallout',
        'AIRMapCalloutSubview',
        'AIRMapCircle',
        'AIRMapHeatmap',
        'AIRMapLocalTile',
        'AIRMapMarker',
        'AIRMapOverlay',
        'AIRMapPolygon',
        'AIRMapPolyline',
        'AIRMapUrlTile',
        'AIRMapWMSTile',
      ],
    },
  },
  assets: ['./src/assets/fonts/'], // stays the same
};
