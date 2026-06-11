import React, { useEffect, useRef } from 'react';
import { Animated, StyleSheet } from 'react-native';

interface SkeletonProps {
  width: string | number;
  height: string | number;
  borderRadius?: number;
  style?: object;
}

export default function SkeletonBox({ width, height, borderRadius = 8, style }: SkeletonProps) {
  // Animation de pulsation (opaque -> transparent -> opaque)
  const opacity = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 1, duration: 800, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0.3, duration: 800, useNativeDriver: true }),
      ])
    ).start();
  }, []);

  return (
    <Animated.View 
      style={[
        styles.skeleton, 
        { 
          width, 
          height, 
          borderRadius,
          opacity 
        }, 
        style 
      ]} 
    />
  );
}

const styles = StyleSheet.create({
  skeleton: {
    backgroundColor: '#E0E0E0', // Gris clair
  },
});